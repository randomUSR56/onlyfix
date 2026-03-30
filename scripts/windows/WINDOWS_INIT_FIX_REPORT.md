# Windows Init Scripts - Root Cause Analysis & Fix Report

## Summary

All four Windows init scripts (`init-dev.bat`, `init-dev-local.bat`, `release.bat`, `release-local.bat`) suffered from multiple critical bugs that prevented successful project initialization on a clean Windows machine. Additionally, the application ran ~17x slower than expected due to Docker volume mount performance issues.

---

## Bugs Found and Fixed

### 1. Missing `.env` File Inside Container (CRITICAL)

**Symptom:** `file_get_contents(/var/www/html/.env): Failed to open stream: No such file or directory` during `key:generate`.

**Root cause:** The script used relative paths (`onlyfix\.env`) which only resolved correctly when the script was executed from the exact repo root directory. If run from any other directory (e.g., `scripts\windows\`), the `.env` copy would silently target the wrong location.

**Fix:** All scripts now resolve `PROJECT_ROOT` using `%~dp0` (the script's own directory) and navigate two levels up. All paths are absolute:
```batch
set "SCRIPT_DIR=%~dp0"
pushd "%SCRIPT_DIR%\..\.."
set "PROJECT_ROOT=%CD%"
popd
set "LARAVEL_DIR=%PROJECT_ROOT%\onlyfix"
```

### 2. Missing SQLite Database Error (CRITICAL)

**Symptom:** `Database file at path [/var/www/html/database/database.sqlite] does not exist` during `migrate:fresh`.

**Root cause:** The artisan `key:generate` command failed silently (because `.env` didn't exist inside the container), so `APP_KEY` was never set. The migration then ran with Laravel's default SQLite fallback since the `.env` with `DB_CONNECTION=mysql` wasn't present. The script had **no error checking** after artisan commands - it printed the success emoji regardless.

**Fix:** Every `docker compose exec` call now checks `%errorlevel%` and triggers rollback on failure. The `-T` flag (non-interactive) replaces the unreliable `-it`/WT_SESSION detection.

### 3. No MySQL Readiness Wait (CRITICAL)

**Symptom:** Race condition. After `docker compose up -d`, MySQL takes 10-30 seconds to initialize. If `composer install` finishes before MySQL is ready, the subsequent `migrate` command fails.

**Root cause:** The script went straight from `up -d` to `composer install` to `migrate` with no wait. On fast machines or with cached images, composer finishes before MySQL.

**Fix:** A polling loop that checks `mysqladmin ping` every second for up to 60 seconds:
```batch
for /L %%i in (1,1,60) do (
    %COMPOSE_CMD% exec -T db mysqladmin ping -h localhost -u root --password=rootSecurePassword123! --silent >nul 2>&1
    if !errorlevel! equ 0 ( set "DB_READY=1" ... )
)
```

### 4. Interactive TTY Flag Breaks Non-Terminal Execution (MAJOR)

**Symptom:** `the input device is not a TTY` errors when running outside Windows Terminal.

**Root cause:** The `WT_SESSION` env var detection was unreliable. Checking for Windows Terminal specifically ignores other valid terminals (ConEmu, mintty, VS Code terminal).

**Fix:** All exec calls now use `-T` (non-interactive) consistently. The init script is a batch process - it never needs interactive input.

### 5. `boost:install` Command Does Not Exist (MINOR)

**Symptom:** `There are no commands defined in the "boost" namespace.`

**Root cause:** Laravel Boost's MCP integration doesn't use an `artisan boost:install` command. It was included in error.

**Fix:** Removed from all scripts.

### 6. Missing `--force` and `--no-interaction` Flags (MAJOR)

**Symptom:** `APPLICATION IN PRODUCTION. Are you sure you want to run this command?` prompt during `migrate:fresh`.

**Root cause:** Laravel detects `APP_ENV=local` but still prompts for destructive commands like `migrate:fresh`. In a non-interactive batch script, this either hangs or auto-selects "Yes" unpredictably.

**Fix:** All artisan commands now include `--force --no-interaction`.

### 7. No Transactional Rollback (MAJOR)

**Symptom:** When the script fails mid-setup (e.g., at migration), it leaves behind: hosts file entries, loopback IPs, running containers, `.env` file, and built Docker images. The user must manually clean up before retrying.

**Fix:** Every script now tracks what it has done via flags (`HOSTS_MODIFIED`, `LOOPBACK_ADDED`, `CONTAINERS_STARTED`, `IMAGES_BUILT`, `ENV_CREATED`). On failure, the `:rollback` label reverses each action:
- Stops containers and removes volumes
- Removes built Docker images
- Deletes the `.env` file
- Removes OnlyFix entries from the hosts file
- Removes loopback IP aliases
- Flushes DNS cache

### 8. Loopback Adapter Name Hardcoded Incorrectly (MAJOR)

**Symptom:** `netsh interface ip add address "Loopback" ...` fails because no adapter named "Loopback" exists on most Windows machines.

**Root cause:** The adapter is typically named "Loopback Pseudo-Interface 1" on Windows, not "Loopback". On some systems a dedicated adapter might exist with a different name.

**Fix:** The script now auto-detects the loopback adapter name from `netsh interface show interface`, falls back to "Loopback Pseudo-Interface 1", and first tests if `127.0.1.x` IPs are already routable (they are on some Windows versions):
```batch
ping -n 1 -w 500 127.0.1.1 >nul 2>&1
if %errorlevel% equ 0 ( goto :loopback_done )
```

### 9. Unicode/Emoji Console Output Garbled (COSMETIC)

**Symptom:** Output like `Γ£à` and `≡ƒöº` instead of emojis.

**Root cause:** Windows cmd.exe defaults to the system's OEM codepage (e.g., CP437 or CP852). Emoji (UTF-8) renders as mojibake.

**Fix:** Added `chcp 65001 >nul 2>&1` at script start. Also replaced all emoji with ASCII-safe tags like `[STEP]`, `[  OK]`, `[ERROR]`, `[WARN]`, `[ INFO]`.

### 10. Release Scripts Run npm on Host (DESIGN FLAW)

**Symptom:** `release.bat` and `release-local.bat` ran `pushd onlyfix && npm install && npm run build` on the host machine, requiring Node.js to be installed locally - defeating the purpose of the Docker-based setup.

**Fix:** Frontend builds now run inside the Node container:
```batch
%COMPOSE_CMD% exec -T node sh -c "npm install && npm run build"
```

### 11. `docker-compose.yml` Had Obsolete `version` Key (MINOR)

**Symptom:** `the attribute 'version' is obsolete` warning on every compose command.

**Root cause:** The `version` key was removed from the Docker Compose spec in v2.

**Fix:** Already removed in a prior commit (the current file doesn't have it).

---

## Performance Fix: Application Slowness

### Symptom

The application was extremely slow on Windows. A simple `php artisan --version` took **9,080ms** (9 seconds). HTTP page loads would have been 15-20+ seconds.

### Root Cause

The Docker volume mount `./onlyfix:/var/www/html` uses VirtioFS (Docker Desktop's file sharing mechanism) to bridge the Windows NTFS filesystem into the Linux container. Every file read traverses this bridge, which has significant per-operation overhead.

The `vendor/` directory alone contains **8,912 PHP files**. On every PHP request, the autoloader opens hundreds of these files. With the bind mount, `find vendor -name '*.php'` took **7,699ms**.

### Fix

Added a named Docker volume `vendor_data` for the `vendor/` directory - the exact same technique already used for `node_modules`:

```yaml
# docker-compose.yml
services:
  app:
    volumes:
      - ./onlyfix:/var/www/html
      - vendor_data:/var/www/html/vendor    # <-- NEW

volumes:
  vendor_data:
    driver: local
```

The named volume stores `vendor/` inside the Docker VM's own ext4 filesystem, bypassing the Windows<->Linux bridge entirely. Composer installs happen inside the container, so the host never touches these files.

### Results

| Benchmark | Before | After | Speedup |
|---|---|---|---|
| `php artisan --version` | 9,080 ms | 456 ms | **19.9x** |
| `find vendor -name '*.php'` | 7,699 ms | 73 ms | **105x** |
| HTTP response (warm) | ~15-20s (est.) | 329 ms | **~50x** |

### Trade-off

The `vendor/` directory is no longer visible on the Windows host filesystem. This means:
- Host-side IDE autocompletion may lose visibility into vendor classes (most IDEs handle this via `composer.json` analysis or a stub file instead)
- To inspect vendor code, use `docker compose exec app cat vendor/...` or configure your IDE to use the container's PHP interpreter

This is the same trade-off already accepted for `node_modules/`.

---

## Files Changed

| File | Change |
|---|---|
| `scripts/windows/init-dev.bat` | Rewritten (prior commit) |
| `scripts/windows/init-dev-local.bat` | Rewritten with transactional rollback, error checking, path resolution |
| `scripts/windows/release.bat` | Rewritten with transactional rollback, error checking, in-container npm build |
| `scripts/windows/release-local.bat` | Rewritten with transactional rollback, error checking, in-container npm build |
| `docker-compose.yml` | Added `vendor_data` volume to `app`, `nginx`, and `queue` services |

---

## Test Results

### init-dev-local.bat (full end-to-end, clean machine state)

```
[  OK] Docker is available and running.
[  OK] Docker Compose: docker compose
[  OK] .env created from .env.example
[  OK] Docker images built.
[  OK] Containers started.
[  OK] MySQL is ready. Waited 14 seconds.
[  OK] Composer dependencies installed.
[  OK] Application key generated.
[  OK] Database migrated and seeded.
[  OK] Storage link created.
[  OK] Wayfinder routes generated.
[  OK] Node container restarted.
[OK] OnlyFix successfully initialized! (LOCAL mode)
```

All 13 steps passed. HTTP response: 329ms warm.

### init-dev.bat (path resolution from alien directory)

```
C:\tmp> c:\Users\dluhid\...\init-dev.bat
Project root: c:\Users\dluhid\GitHubRepositories\onlyfix
[ERROR] Administrator privileges required.
```

Correctly resolves project root even when invoked from a completely unrelated directory, and correctly requires admin before proceeding.
