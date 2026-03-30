# OnlyFix Windows Init Script - Root Cause Analysis & Fix Report

## Summary

The `scripts/windows/init-dev.bat` script failed to initialize the project on a clean Windows machine with Docker Desktop as the only dependency. The script reported "success" at the end despite multiple critical failures during execution. Seven distinct bugs were identified and fixed.

---

## Bug #1: `.env` file not found inside the container

### Symptom
```
ErrorException
file_get_contents(/var/www/html/.env): Failed to open stream: No such file or directory
at vendor/laravel/framework/src/Illuminate/Foundation/Console/KeyGenerateCommand.php:100
```

### Root Cause
The script used a **relative path** (`onlyfix\.env`) to copy the `.env.example` file, but this path is relative to the current working directory of the shell, not the repo root. The script lives in `scripts/windows/` but assumed it would always be run from the repo root. When run from its own directory (as the error output shows: `PS C:\...\scripts\windows>`), the copy target was `scripts/windows/onlyfix/.env` which doesn't exist locally -- and more importantly, Docker mounts `./onlyfix` from the **repo root** as specified in `docker-compose.yml`, so the `.env` would never appear in the container.

### Fix
Resolved the script's own directory (`%~dp0`) and computed the project root by going two levels up (`..\..\`). All paths now use absolute references: `%PROJECT_ROOT%\onlyfix\.env.example` and `%PROJECT_ROOT%\onlyfix\.env`. Additionally, the `docker compose` commands now run from `%PROJECT_ROOT%` via `pushd`.

---

## Bug #2: Database migration fails - MySQL not ready

### Symptom
```
Illuminate\Database\QueryException
Database file at path [/var/www/html/database/database.sqlite] does not exist.
```

### Root Cause
This error is **deceptive**. It says "sqlite" but the `.env` specifies `DB_CONNECTION=mysql`. What actually happened:

1. `docker compose up -d` started all containers including MySQL
2. The script immediately ran `composer install` then `migrate:fresh --seed`
3. MySQL 8.0 takes 10-30 seconds to initialize on first run (creating system tables, setting up users)
4. When the MySQL connection failed, Laravel's database config falls back to `env('DB_CONNECTION', 'sqlite')` -- but since the `.env` was also missing (Bug #1), the default `'sqlite'` was used
5. The SQLite database file doesn't exist either, producing the confusing error

### Fix
Added a MySQL readiness check loop that polls `mysqladmin ping` for up to 60 seconds before proceeding with any artisan commands. Progress is printed every 5 seconds. If MySQL doesn't become ready in 60s, the script fails with a clear error and rolls back.

---

## Bug #3: `migrate:fresh --seed` prompts for confirmation in production

### Symptom
```
APPLICATION IN PRODUCTION.
Are you sure you want to run this command?
```

### Root Cause
Two issues combined:
1. The `.env` was missing (Bug #1), so `APP_ENV` defaulted to `production`
2. The artisan command lacked the `--force` flag to bypass confirmation prompts
3. The old script used `-it` flags on `docker compose exec` to allow interactive TTY input -- but this is fragile and causes errors when no TTY is available

### Fix
- All `docker compose exec` calls now use `-T` (disable pseudo-TTY allocation) instead of `-it`
- All artisan commands include `--force --no-interaction` flags
- The `-T` flag is correct for scripted/non-interactive contexts and works consistently across cmd.exe, PowerShell, and Windows Terminal

---

## Bug #4: No error checking - script always reports success

### Symptom
Every step printed a success message regardless of the actual exit code:
```
[key:generate throws exception]
Alkalmazaskulcs generalva    <-- "Key generated" despite the error above

[migrate:fresh fails]
Adatbazis migralva es seedelve  <-- "Database migrated" despite the error above
```

### Root Cause
The script had **zero error checking** after any `docker compose exec` command. Every step simply ran the command and then unconditionally printed a success emoji.

### Fix
Every step now checks `%errorlevel%` immediately after execution. On failure, the script jumps to the `:rollback` label which undoes all changes made so far (see Bug #5).

---

## Bug #5: No rollback mechanism - failed init leaves system dirty

### Symptom
A failed init left behind:
- Entries in the Windows hosts file
- Running Docker containers consuming resources
- Built Docker images consuming disk space
- Loopback IP aliases on network interfaces
- A partial `.env` file

There was no way to "undo" the init.

### Root Cause
The script had no concept of tracking what it had done, and no rollback path.

### Fix
Implemented a **transactional rollback system** using flag variables:
- `HOSTS_MODIFIED`, `LOOPBACK_ADDED`, `CONTAINERS_STARTED`, `IMAGES_BUILT`, `ENV_CREATED`
- Each flag is set to `1` after its respective step completes
- The `:rollback` label checks each flag and reverses only the actions that were actually taken
- Rollback order is: stop containers -> remove images -> delete `.env` -> clean hosts file -> remove loopback IPs -> flush DNS

The success message now also includes manual teardown instructions.

---

## Bug #6: Loopback IP setup targets non-existent adapter

### Symptom
```
netsh interface ip add address "Loopback" 127.0.1.1 255.255.255.0 >nul 2>&1
```
Silently failed because Windows doesn't have a network adapter named "Loopback" by default.

### Root Cause
The script assumed a Microsoft Loopback Adapter was installed and named "Loopback". On a clean Windows install, this adapter doesn't exist. The `127.0.1.x` IP range is part of the loopback `127.0.0.0/8` block and is **usually routable by default** on Windows without any adapter configuration.

### Fix
The script now:
1. Auto-detects if a Loopback adapter exists by parsing `netsh interface show interface`
2. Tests if `127.0.1.1` is already reachable via `ping`
3. If reachable (common case), skips adapter configuration entirely
4. If not reachable, tries the standard "Loopback Pseudo-Interface 1" name
5. Uses `/32` subnet mask (`255.255.255.255`) instead of `/24` (`255.255.255.0`) to avoid polluting the routing table

---

## Bug #7: `boost:install` command doesn't exist

### Symptom
```
ERROR  There are no commands defined in the "boost" namespace.
```

### Root Cause
`laravel/boost` is a development tool package. It does not ship a `boost:install` artisan command. The command was likely confused with another package's install step.

### Fix
Removed the `boost:install` step entirely. The `laravel/boost` package works without any artisan command -- it's auto-discovered by Laravel's package discovery (confirmed in the composer install output).

---

## Bug #8: Emoji garbling in Windows console

### Symptom
```
Γ£à Composer fuggosegek telepitve
≡ƒöº NPM fuggosegek telepitese...
```
Instead of readable status messages.

### Root Cause
Windows `cmd.exe` defaults to a legacy codepage (usually 437 or 1252) that cannot render UTF-8 emoji characters. The bytes of the UTF-8 encoded emojis get interpreted as Windows-1252, producing garbled output.

### Fix
1. Added `chcp 65001` at the top of the script to switch to UTF-8 codepage
2. Replaced all emoji-based status indicators with ASCII text tags: `[  OK]`, `[STEP]`, `[ERROR]`, `[WARN]`, `[ INFO]`

This ensures the output is readable in any terminal: cmd.exe, PowerShell, Windows Terminal, or CI runners.

---

## Bug #9: `version: '3.8'` in docker-compose.yml

### Symptom
```
level=warning msg="the attribute `version` is obsolete, it will be ignored, please remove it"
```

### Root Cause
Docker Compose V2 no longer uses the `version` key. It's ignored but produces a warning on every single compose command, cluttering the output.

### Fix
Removed `version: '3.8'` from `docker-compose.yml`.

---

## Changes Made

| File | Change |
|------|--------|
| `scripts/windows/init-dev.bat` | Complete rewrite with all fixes above |
| `docker-compose.yml` | Removed obsolete `version: '3.8'` line |

## How to Undo / Teardown

After a successful init, to completely reverse all changes:

```batch
REM 1. Stop containers, remove volumes and locally-built images
cd C:\Users\dluhid\GitHubRepositories\onlyfix
docker compose down -v --rmi local

REM 2. Remove hosts file entries (run as admin)
REM    Open %SystemRoot%\System32\drivers\etc\hosts in a text editor
REM    Delete the "# OnlyFix Project" block (6 lines)

REM 3. Delete the generated .env file (optional)
del onlyfix\.env

REM 4. Flush DNS cache
ipconfig /flushdns
```

Loopback IPs in the `127.0.1.x` range are part of the standard `127.0.0.0/8` loopback block and are routable by default on Windows. No removal is needed.

If the script **fails mid-setup**, the automatic rollback handles all of the above.
