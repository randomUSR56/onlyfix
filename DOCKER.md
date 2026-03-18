# OnlyFix Docker Setup

Docker-based development environment for OnlyFix project.

## Quick Start

### Windows
```powershell
# PowerShell (Admin)
make init
```

### Linux/macOS
```bash
# Terminal (sudo)
sudo make init
```

**Access:**
- 🌐 http://onlyfix.local
- 📧 http://mailpit.onlyfix.local:8025
- 🗄️  http://phpmyadmin.onlyfix.local:8080

---

## Services

| Service | URL | Credentials |
|---------|-----|-------------|
| App | http://onlyfix.local | - |
| Mailpit | http://mailpit.onlyfix.local:8025 | - |
| phpMyAdmin | http://phpmyadmin.onlyfix.local:8080 | `onlyfix` / `onlyfixSecurePass456!` |
| MySQL | db.onlyfix.local:3306 | `onlyfix` / `onlyfixSecurePass456!` |
| Vite | http://node.onlyfix.local:5173 | - |

---

## Commands

### Setup
```bash
make setup      # Hosts + loopback setup
make hosts      # Update hosts file only
make build      # Build Docker images
make start      # Start containers
make stop       # Stop containers
make down       # Stop and remove containers
make restart    # Restart containers
```

### Laravel
```bash
make install    # Install Composer + NPM dependencies
make key        # Generate app key
make migrate    # Run migrations
make seed       # Seed database
make fresh      # Fresh migration + seed
make storage    # Create storage link
make cache-clear # Clear all caches
```

### Queue
```bash
make queue-listen   # Start queue (dev mode, live reload)
make queue-work     # Start queue (production mode)
make queue-restart  # Restart queue worker
```

### Development
```bash
make shell      # Bash in app container
make shell-node # Shell in node container
make tinker     # Laravel Tinker
make test       # Run tests
make logs       # Show container logs
make ps         # List containers
```

### Cleanup
```bash
make clean      # Remove containers + volumes
make prune      # Docker system prune
```

---

## Manual Setup

### 1. Hosts File

**Windows:** `C:\Windows\System32\drivers\etc\hosts`
**Linux/macOS:** `/etc/hosts`

```
127.0.1.1       onlyfix.local
127.0.1.2       db.onlyfix.local
127.0.1.3       mailpit.onlyfix.local
127.0.1.4       node.onlyfix.local
127.0.1.5       phpmyadmin.onlyfix.local
```

### 2. Loopback (Linux/macOS only)

**Linux:**
```bash
sudo ip addr add 127.0.1.1/8 dev lo
sudo ip addr add 127.0.1.2/8 dev lo
sudo ip addr add 127.0.1.3/8 dev lo
sudo ip addr add 127.0.1.4/8 dev lo
sudo ip addr add 127.0.1.5/8 dev lo
```

**macOS:**
```bash
sudo ifconfig lo0 alias 127.0.1.1
sudo ifconfig lo0 alias 127.0.1.2
sudo ifconfig lo0 alias 127.0.1.3
sudo ifconfig lo0 alias 127.0.1.4
sudo ifconfig lo0 alias 127.0.1.5
```

### 3. Environment

```bash
cp .env.docker.example onlyfix/.env
```

### 4. Docker

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec node npm install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan storage:link
```

---

## Troubleshooting

### Containers not starting
```bash
docker-compose logs
docker-compose down -v
docker-compose up -d --build
```

### DNS not resolving
```bash
# Windows
ipconfig /flushdns

# macOS
sudo dscacheutil -flushcache
sudo killall -HUP mDNSResponder

# Linux
sudo systemd-resolve --flush-caches
```

### HMR not working
Check `.env`:
```
VITE_DEV_SERVER_URL=http://node.onlyfix.local:5173
```

### Queue not processing
```bash
make queue-restart
docker-compose logs queue
```

### Permission errors
```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
```

### TypeScript errors in VS Code

If you see TypeScript errors like "Cannot find module 'vite'":

**Cause:** NPM packages not installed on host machine (only in Docker container).

**Solution:** The setup script should have installed them automatically. If not:

```bash
cd onlyfix
npm install
```

Then reload VS Code: `Ctrl+Shift+P` → "Developer: Reload Window"

**Note:** This is for IntelliSense only. The Docker container has its own `node_modules`.

---

## Log Locations

- **Nginx:** `docker/logs/nginx/`
- **MySQL:** `docker/logs/mysql/`
- **Laravel:** `onlyfix/storage/logs/`
- **Container:** `docker-compose logs -f <service>`

---

## Laravel Boost / MCP Integration

Laravel Boost's MCP server requires special setup in a Dockerized environment.
The default `boost:install` flow breaks because the DB hostname `db` only resolves
inside the Docker network.

See **`BOOST_DOCKER.md`** for the full explanation, correct setup steps, and
suggested improvements.
