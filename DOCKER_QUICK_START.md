# OnlyFix Docker Setup - Quick Reference

## Előfeltételek

| Szükséges | Telepítés |
|-----------|-----------|
| **Git** | [git-scm.com](https://git-scm.com/) |
| **Docker Desktop** | [docker.com/get-docker](https://docs.docker.com/get-docker/) vagy `brew install --cask docker` (macOS) |

> **Fontos:** Docker Desktop-nak **futnia kell** mielőtt a `make init`-et elindítod!

**Opcionális** (VS Code IntelliSense-hez):
- Node.js 20+ — [nodejs.org](https://nodejs.org/)

---

## One-Command Setup

```bash
git clone https://github.com/randomUSR56/onlyfix.git
cd onlyfix
make init
```

A `make init` automatikusan `sudo`-t kér a hosts fájl és loopback interfészek beállításához.
**NEM kell `sudo make init`-et használni** — a parancs maga kezeli a jogosultságokat.

---

## Mit csinál a `make init`?

| Lépés | Leírás |
|-------|--------|
| 1. `setup` | Loopback IP-k (127.0.1.1–5), `/etc/hosts` bejegyzések, DNS cache ürítés, `.env` másolás |
| 2. `build` | Docker image-ek build-elése (PHP 8.3-FPM, Nginx, Node.js, MySQL 8.0, stb.) |
| 3. `start` | 7 Docker konténer indítása |
| 4. `install` | Composer + NPM csomagok telepítése (konténereken belül) |
| 5. `key` | Laravel alkalmazás kulcs generálása |
| 6. `migrate` | Adatbázis migrációk futtatása (MySQL) |
| 7. `seed` | Teszt adatok betöltése (felhasználók, autók, jegyek) |
| 8. `storage` | Storage link létrehozása |
| 9. `wayfinder` | TypeScript route definíciók generálása |

---

## Teszt Fiókok

| Szerepkör | Email | Jelszó |
|-----------|-------|--------|
| **Admin** | `admin@example.com` | `password` |
| **Szerelő** | `mechanic@example.com` | `password` |
| **Felhasználó** | `test@example.com` | `password` |

---

## Hozzáférési URL-ek

| Szolgáltatás | URL | Leírás |
|-------------|-----|--------|
| **Alkalmazás** | http://onlyfix.local | Fő webalkalmazás |
| **Mailpit** | http://mailpit.onlyfix.local:8025 | Email tesztelés |
| **phpMyAdmin** | http://phpmyadmin.onlyfix.local:8080 | Adatbázis kezelő |
| **Vite HMR** | http://node.onlyfix.local:5173 | Frontend hot reload |
| **MySQL** | db.onlyfix.local:3306 | Adatbázis közvetlen elérés |

---

## Adatbázis

| Paraméter | Érték |
|-----------|-------|
| Host (konténerből) | `db` |
| Host (hostról) | `db.onlyfix.local:3306` |
| Adatbázis | `onlyfix` |
| Felhasználó | `onlyfix` |
| Jelszó | `onlyfixSecurePass456!` |
| Root jelszó | `rootSecurePassword123!` |

---

## Napi Fejlesztés

```bash
make start          # Konténerek indítása
make stop           # Konténerek leállítása
make logs           # Logok megtekintése (Ctrl+C kilépés)
make ps             # Futó konténerek listázása

make shell          # Bash a PHP konténerben
make shell-node     # Shell a Node konténerben
make tinker         # Laravel Tinker (REPL)

make migrate        # Migrációk futtatása
make fresh          # Adatbázis újraépítés + seed
make cache-clear    # Összes cache törlése
make test           # Tesztek futtatása
```

---

## Hibaelhárítás

### Docker nem fut
```bash
make check-docker   # Ellenőrzi, hogy Docker elérhető-e
```
Indítsd el a Docker Desktop-ot, majd próbáld újra.

### Üres oldal / JS hiba
```bash
docker compose restart node   # Vite dev server újraindítás
```
Böngészőben: `Cmd+Shift+R` (macOS) vagy `Ctrl+Shift+R` (Linux/Windows)

### Adatbázis problémák
```bash
make fresh          # Teljes adatbázis újraépítés teszt adatokkal
```

### Teljes újrakezdés
```bash
make clean          # Konténerek + adatok törlése
make init           # Mindent újra
```

---

Részletes dokumentáció: `DOCKER.md`
