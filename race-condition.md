## Root Cause Report: Node Container Failures

### The Problem

The `node` service in `docker-compose.yml` is a **one-shot build container**: it runs `npm install && npm run build`, then exits permanently because `restart: "no"` is set. This is by design -- it's not a long-running service like `app` or `nginx`.

### Why It Crashed Your Init Script

Your error was:
```
[STEP] Frontend assets építése (production)...
service "node" is not running
```

The timeline:
1. `docker compose up -d` starts all 7 services including `node`
2. The `node` container runs `npm install && npm run build`, finishes in ~5s, and **exits with code 0** (success)
3. The script proceeds through MySQL wait, composer install, key generation, migrations (~15-30 seconds)
4. By step 12, the script tries `exec -T node sh -c "npm install && npm run build"` 
5. `exec` requires a **running** container -- but `node` exited long ago --> **FAIL**
6. The new rollback logic kicks in and tears everything down

### Why This Wasn't The Swagger Change

The swagger migration (adding `wotz/laravel-swagger-ui` to composer.json, removing the `mkdir -p /app/public/swagger-ui` from docker-compose) did **not** cause this. The node container's command (`npm install && npm run build`) didn't change in a way that could break it. The real bug was always latent -- it's a **race condition** that depends on how fast the preceding script steps complete vs. how long the node build takes.

### Why It "Worked Before" (Sometimes)

The **dev init scripts** used `restart node`. On slower machines or first runs (where `npm install` takes longer), the node container might still be running when `restart` was called, so it appeared to work. On your Mac today, the node container finished before the script reached that step.

The **release scripts** used `exec -T node` -- which was **always broken** for the same reason. It worked on Windows only by accident (slower preceding steps, or the exec happened while the container was still building).

### The Fix Applied (All 8 Scripts)

| Before (broken) | After (fixed) |
|---|---|
| `docker compose restart node` | `docker compose run --rm node sh -c "npm install && npm run build"` |
| `docker compose exec -T node sh -c "..."` | `docker compose run --rm node sh -c "npm install && npm run build"` |

**Why `run --rm` is the correct command:**
- `exec` -- attaches to a **running** container (fails if exited)
- `restart` -- restarts **running** containers (no-ops on exited ones)
- `run --rm` -- **creates a fresh ephemeral container** from the service definition (same image, same volumes, same working directory), runs the command, and removes the container when done. Always works regardless of the original container's state.

### Scripts Fixed (8 total)

**Unix:** `init-dev.sh`, `init-dev-local.sh`, `release-local.sh`, `release.sh`
**Windows:** `init-dev.bat`, `init-dev-local.bat`, `release-local.bat`, `release.bat`

Made changes.