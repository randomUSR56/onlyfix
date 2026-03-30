## OnlyFix: Frontend Black Screen — Root Cause & Fix Report

**Symptom:** The login page (and all other pages) rendered a black screen. Vue never mounted. The application was functionally useless in the browser despite the Laravel backend responding correctly.

---

### What Was Failing

The browser console showed three distinct failures on every page load:

1. `WebSocket connection to 'ws://onlyfix.local/?token=...' failed`
2. `WebSocket connection to 'ws://localhost:5173/?token=...' failed`
3. `[vite] failed to connect to websocket`
4. Three separate `403 Forbidden` errors on `node_modules/.vite/deps/vue.js`, `@inertiajs_vue3.js`, and `laravel-vite-plugin_inertia-helpers.js`

Because Vue itself was returning 403, it never loaded — which is why the page was visually blank despite the HTML document returning 200.

---

### Root Cause

There were two compounding failures, both caused by running Vite's **development server** inside Docker Desktop for Windows.

**Cause 1 — Docker Desktop for Windows cannot forward WebSocket connections**

The application was running a Vite dev server (`npm run dev`) inside a Docker container, with port 5173 mapped to the host. Docker Desktop on Windows uses a userspace proxy to handle port forwarding. This proxy handles standard HTTP traffic correctly, but **silently drops WebSocket upgrade handshakes**. Vite 7 changed how HMR (Hot Module Replacement) initialises — in earlier versions, a failed WebSocket connection degraded gracefully. In Vite 7, the client `await`s the WebSocket connection at startup. When it fails, it throws an unhandled exception before Vue has had a chance to mount. This is the direct cause of the black screen.

**Cause 2 — Vite's dependency pre-bundler produced a stale cache**

Vite pre-bundles third-party dependencies (Vue, Inertia, etc.) into `node_modules/.vite/deps/` and stamps them with a `browserHash` that it embeds into the `@vite/client` script. Every time the Vite dev server restarted (e.g. after a `docker compose restart`), it generated a new `browserHash`. However, because `node_modules` is stored on a named Docker volume (which persists across restarts), the `.vite/deps/_metadata.json` file on disk would retain the old hash while the new Vite server expected a different one. Vite responds to this mismatch with `504 Outdated Optimize Dep` — but because the request was proxied through nginx, nginx converted that 504 into a `403 Forbidden` for the browser. The result: Vue and Inertia could not be fetched, and the app could not render.

These two issues occurred simultaneously. Even if the WebSocket problem had been worked around, the 403s on Vue would still produce a black screen.

---

### What We Tried First

Before landing on the final fix, we attempted to route Vite traffic through nginx as a proxy — the idea being that the browser would talk to nginx on port 80 (which Docker Desktop does proxy correctly), and nginx would internally forward to the node container over Docker's bridge network. This resolved the HTTP asset loading, but:

- The WebSocket upgrade still failed from the Windows host side, just one hop later (nginx instead of node directly)
- The `browserHash` mismatch remained unsolved

Setting `hmr: false` in `vite.config.ts` suppressed the WebSocket attempt but didn't fix the dep cache issue, and we confirmed in the browser console that the old cached Vite client was still requesting the stale dep hashes.

---

### The Fix

The correct solution was to **stop using Vite's development server entirely in the Docker environment**, and instead build the assets once and serve them as static files.

**`docker-compose.yml` — node container**

Changed the node container command from `npm run dev` to `npm run build`. Set `restart: "no"` since the container's job is done once the build completes. Removed the port 5173 mapping entirely (the Vite dev server no longer runs, so there is nothing to expose).

**`docker/nginx/nginx.conf`**

Removed all Vite proxy location blocks (`/@vite/`, `/node_modules/`, `/resources/`, `/__vite_ping`). These were only needed to route traffic to a live dev server. Replaced them with a simple cache header block for `/build/` — the directory where `npm run build` outputs compiled assets.

**`onlyfix/vite.config.ts`**

Set `hmr: false` when `DOCKER=1` to ensure that even if the config were used in a dev-server context, no WebSocket connection would be attempted.

---

### Why This Works

`npm run build` produces a `public/build/` directory containing fully compiled, content-hashed static files (`app-C2O8g-wW.js`, `app-CBphH_kV.css`, etc.) alongside a `manifest.json`. Laravel's `@vite()` Blade directive reads this manifest in production mode and injects plain `<script src="/build/assets/...">` and `<link href="/build/assets/...">` tags — no dev server, no WebSocket, no dependency optimizer, no hash mismatch. Nginx serves these files directly from the filesystem. The browser receives everything on the first request and Vue mounts immediately.

---

### Summary Table

| Issue | Why It Happened | Fix |
|---|---|---|
| Black screen / Vue never mounts | Vite 7 HMR client throws on WebSocket failure, blocking script execution | Removed dev server; `hmr: false` in Docker |
| `403` on Vue, Inertia, helpers | `browserHash` mismatch between restarted Vite server and cached `.vite/deps/` | Removed dev server; assets compiled once with `npm run build` |
| WebSocket always fails | Docker Desktop for Windows userspace proxy drops WebSocket upgrade handshakes | No fix possible at Docker layer; problem eliminated by not running a WebSocket server |

---

### Going Forward

When frontend code changes need to be deployed, the workflow is:

```
docker compose up node   # runs npm install && npm run build, then exits
docker compose restart nginx
```

Nginx picks up the new files in `public/build/` immediately. No container stays alive just to run a dev server.