# OnlyFix Docker Setup - Quick Reference

## One-Command Setup

### Windows (PowerShell Admin)
```powershell
make init
```

### Linux/macOS (Terminal)
```bash
sudo make init
```

---

## What Gets Installed

1. ✅ Loopback interfaces (127.0.1.1-5) - Linux/macOS only
2. ✅ Hosts file entries (all platforms)
3. ✅ Docker containers (7 services)
4. ✅ Composer dependencies
5. ✅ NPM dependencies
6. ✅ Laravel app key
7. ✅ Database migrations
8. ✅ Storage links

---

## Access URLs

| Service | URL | Port |
|---------|-----|------|
| **App** | http://onlyfix.local | 80 |
| **Mailpit** | http://mailpit.onlyfix.local:8025 | 8025 |
| **phpMyAdmin** | http://phpmyadmin.onlyfix.local:8080 | 8080 |
| **Vite HMR** | http://node.onlyfix.local:5173 | 5173 |
| **MySQL** | db.onlyfix.local:3306 | 3306 |

---

## Database Credentials

**MySQL:**
- Host: `db` (inside Docker) or `db.onlyfix.local:3306` (from host)
- Database: `onlyfix`
- User: `onlyfix`
- Password: `onlyfixSecurePass456!`
- Root Password: `rootSecurePassword123!`

**phpMyAdmin:**
- URL: http://phpmyadmin.onlyfix.local:8080
- Server: `db`
- User: `onlyfix`
- Password: `onlyfixSecurePass456!`

---

## Common Commands

```bash
make start          # Start containers
make stop           # Stop containers
make logs           # View logs
make shell          # Open shell in app container
make tinker         # Laravel Tinker
make migrate        # Run migrations
make queue-listen   # Start queue (dev mode)
make cache-clear    # Clear all caches
```

See `DOCKER.md` for full documentation.
