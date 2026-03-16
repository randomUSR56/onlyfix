# Laravel Boost + Docker: Setup Guide & Troubleshooting

This document explains how Laravel Boost's MCP server integrates with the Dockerized
development environment, why the default `boost:install` flow breaks in this project,
and how to set it up correctly.

---

## What is Laravel Boost MCP?

Laravel Boost installs an MCP (Model Context Protocol) server into your IDE. VS Code
connects to this server as a background process and uses it to give GitHub Copilot
live access to your running application — querying the database, calling Artisan
commands, reading browser logs, searching Laravel docs, etc.

VS Code reads the server configuration from `.vscode/mcp.json` in the **workspace root**.

---

## Root Cause: Why `boost:install` Fails Out of the Box

Three compounding problems occur when running this project with Docker:

### Problem 1 — Running `boost:install` on the host machine

```bash
# ❌ Wrong — run from your Mac terminal
php artisan boost:install
```

The app's `.env` sets `DB_HOST=db`. The hostname `db` only resolves inside the Docker
network. Running Artisan on the host machine causes:

```
SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for db failed
```

**Fix:** Always run Artisan commands inside the `app` container:

```bash
docker compose exec app php artisan boost:install
```

### Problem 2 — Boost writes `mcp.json` to the wrong directory

This project has a nested structure:

```
/onlyfix/              ← VS Code workspace root (docker-compose.yml lives here)
  onlyfix/             ← Laravel application
    .vscode/
      mcp.json         ← Boost writes here (WRONG for VS Code)
```

VS Code only reads `.vscode/mcp.json` from the **workspace root** — the folder you
opened in VS Code. Because `boost:install` runs inside the `app` container whose
working directory is `/var/www/html` (mapped to `./onlyfix/`), the generated file
ends up one level too deep and VS Code never sees it.

**Fix:** Also place `mcp.json` at the workspace root:

```
/onlyfix/
  .vscode/
    mcp.json           ← VS Code reads this ✓
```

### Problem 3 — The generated command runs on the host, not in Docker

Even if VS Code found the file, the default generated content is:

```json
{
    "servers": {
        "laravel-boost": {
            "command": "php",
            "args": ["artisan", "boost:mcp"]
        }
    }
}
```

VS Code launches this as a host process. It would fail for the same DB reason as
Problem 1 — `db` doesn't resolve on the host.

### Problem 4 — `.vscode/` is gitignored inside the Laravel project

The inner `onlyfix/.gitignore` (Laravel's default) contains `/.vscode`, so any file
Boost generates there is **never committed**. Every developer cloning the repo must
redo the setup.

The outer workspace-root `.gitignore` does **not** ignore `.vscode`, so the fix file
at the workspace root CAN and SHOULD be committed.

---

## Setup (automatic since these fixes were applied)

`make init` now handles everything. After cloning and running `make init`, Boost is
fully configured with no manual steps.

```bash
git clone https://github.com/randomUSR56/onlyfix.git
cd onlyfix
make init
```

`make init` ends with a `make boost` step that runs `boost:install` inside the
container. The workspace-root `.vscode/mcp.json` (committed to git) already contains
the correct Docker-aware config — VS Code picks it up immediately.

After `make init` finishes, reload VS Code:

```
F1 → Developer: Reload Window
```

The `laravel-boost` server will appear in F1 → **MCP: List Servers**.

### Refresh Boost manually

If you ever need to re-run the Boost install (e.g. after upgrading the package):

```bash
make boost
# or directly:
docker compose exec app php artisan boost:install
```

### What `.vscode/mcp.json` at the workspace root contains

```json
{
    "servers": {
        "laravel-boost": {
            "command": "docker",
            "args": ["compose", "exec", "-T", "app", "php", "artisan", "boost:mcp"],
            "cwd": "${workspaceFolder}"
        }
    }
}
```

- `docker compose exec -T app` — runs the MCP server process **inside** the container
  where `db` resolves correctly.
- `-T` — disables TTY so stdin/stdout work as a proper MCP stdio transport.
- `cwd: "${workspaceFolder}"` — Docker Compose finds `docker-compose.yml` relative to
  the workspace root; no absolute paths, works on any machine.

---

## Daily Usage

- Containers must be running (`make start`) before VS Code starts the MCP server.
- VS Code connects to the MCP server automatically when Copilot Chat is opened.
- If the server shows as "Stopped", start containers and use F1 → MCP: List Servers
  → click `laravel-boost` → Start.

---

## Suggested Improvements (implemented)

### ✅ `make boost` target added to Makefile

Runs `boost:install` inside the container. Safe to call at any time.

```bash
make boost
```

### ✅ `make boost` wired into `make init`

Boost is installed automatically as the final step of `make init`. No manual action
needed after cloning.

### ✅ `.vscode/mcp.json` committed at workspace root

The outer `.gitignore` previously ignored all of `.vscode/`. A negation rule was added:

```gitignore
.vscode/
!.vscode/mcp.json
```

This allows the workspace-root `mcp.json` to be committed and shared, while keeping
other `.vscode/` files (settings, extensions, launch configs) local-only.

### ⬜ Consider a `make doctor` / `make check` target

Could verify containers are running and the MCP server command is correct — useful
for onboarding troubleshooting.

---

## File Locations Reference

| File | Committed? | Purpose |
|------|-----------|---------|
| `.vscode/mcp.json` (workspace root) | ✅ Yes | VS Code reads this to start the MCP server |
| `onlyfix/.vscode/mcp.json` | ❌ No (gitignored) | Generated by `boost:install`, redundant |
| `onlyfix/.github/copilot-instructions.md` | ✅ Yes | Copilot guidelines generated by Boost |
| `onlyfix/boost.json` | ✅ Yes | Boost configuration (agents, editors) |
