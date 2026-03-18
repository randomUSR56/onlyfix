# onlyfix

An open source project under MIT license where we perform CRUD operations about issues that a user experiences with their vehicle (ticket-style). The project is accessible on all major platforms such as mobile, desktop and web. A longer and more detailed description can be found in the repository's README.md file.

# Admin

Az onlyfix szoftvercsomag tartalmaz egy (natív) adminisztrátori segédprogramot is amely elérhető a következő GitHub hivatkozáson: https://github.com/randomUSR56/Admin

## Technológiai Stack

### Backend

- **PHP**: ^8.2
- **Laravel Framework**: ^12.0
- **Inertia.js Laravel**: ^2.0 - Server-side adapter a modern SPA élményhez
- **Laravel Fortify**: ^1.30 - Autentikáció és felhasználókezelés
- **Laravel Wayfinder**: ^0.1.9 - Routing és navigáció
- **Laravel Tinker**: ^2.10.1 - Interaktív REPL konzol

### Frontend

- **Vue.js**: ^3.5.13 - Progresszív JavaScript framework
- **TypeScript**: ^5.2.2 - Type-safe JavaScript fejlesztés
- **Inertia.js Vue3**: ^2.1.0 - Client-side adapter
- **Tailwind CSS**: ^4.1.1 - Utility-first CSS framework
- **Vite**: ^7.0.4 - Modern build tool és dev server

### UI Komponensek és Könyvtárak

- **Reka UI**: ^2.4.1 - Vue komponens könyvtár
- **Lucide Vue Next**: ^0.468.0 - Ikonok
- **VueUse**: ^12.8.2 - Vue Composition API utilities
- **Class Variance Authority**: ^0.7.1 - CSS osztály menedzsment
- **Tailwind Merge**: ^3.2.0 - Tailwind osztályok egyesítése
- **TW Animate CSS**: ^1.2.5 - Animációk

### Fejlesztői Eszközök

- **ESLint**: ^9.17.0 - Kód minőség ellenőrzés
- **Prettier**: ^3.4.2 - Kód formázás
- **Laravel Pint**: ^1.18 - PHP kód formázás
- **Pest**: ^4.1 - Modern PHP testing framework
- **Laravel Pail**: ^1.2.2 - Log viewer
- **Laravel Sail**: ^1.41 - Docker alapú fejlesztői környezet

## Fejlesztői Környezet Beállítása

### Előfeltételek

- Git
- Docker Desktop ([telepítés](https://docs.docker.com/get-docker/) vagy macOS-en: `brew install --cask docker`)

> Opcionális: Node.js 20+ a host gépen (VS Code IntelliSense-hez)

### Telepítés (Docker — ajánlott)

```bash
# 1. Klónozás
git clone https://github.com/randomUSR56/onlyfix.git
cd onlyfix

# 2. Indítsd el a Docker Desktop-ot, majd:
make init
```

Ez a parancs **mindent automatikusan elvégez**:

- Hálózat konfiguráció (loopback IP-k, hosts bejegyzések)
- Docker image-ek build-elése és konténerek indítása
- Composer + NPM csomagok telepítése
- Laravel kulcs generálás, migrációk, seed (teszt adatok)
- Storage link és TypeScript route generálás

> A `make init` automatikusan `sudo`-t kér ahol szükséges. **NEM kell `sudo make init`-et írni.**

### Teszt Fiókok

| Szerepkör   | Email                  | Jelszó     |
| ----------- | ---------------------- | ---------- |
| Admin       | `admin@example.com`    | `password` |
| Szerelő     | `mechanic@example.com` | `password` |
| Felhasználó | `test@example.com`     | `password` |

### Hozzáférési URL-ek

| Szolgáltatás | URL                                  |
| ------------ | ------------------------------------ |
| Alkalmazás   | http://onlyfix.local                 |
| Mailpit      | http://mailpit.onlyfix.local:8025    |
| phpMyAdmin   | http://phpmyadmin.onlyfix.local:8080 |

Részletes Docker dokumentáció: [DOCKER_QUICK_START.md](DOCKER_QUICK_START.md)

### Telepítés (helyi, Docker nélkül)

Ehhez szükséges: PHP 8.2+, Composer, Node.js, MySQL.

```bash
cd onlyfix
# Backend függőségek telepítése
composer install

# .env fájl létrehozása
cp .env.example .env

# Alkalmazás kulcs generálása
php artisan key:generate

# Adatbázis migrációk
php artisan migrate

# Frontend függőségek telepítése
npm install

# Frontend build
npm run build
```

### Fejlesztési Módok

#### Alap fejlesztési mód

```bash
composer dev
```

Ez a parancs párhuzamosan indítja el:

- **PHP fejlesztői server** (kék szín)
- **Queue worker** (lila szín)
- **Log viewer (Pail)** (rózsaszín szín)
- **Vite dev server** (narancssárga szín)

#### SSR (Server-Side Rendering) fejlesztési mód

```bash
composer dev:ssr
```

Ez a parancs a következőket futtatja:

- SSR build készítése
- PHP fejlesztői server
- Queue worker
- Log viewer
- Inertia SSR server

### Hasznos Parancsok

#### Frontend fejlesztés

```bash
npm run dev          # Vite dev server indítása
npm run build        # Production build
npm run build:ssr    # SSR build
npm run format       # Kód formázás (Prettier)
npm run format:check # Formázás ellenőrzése
npm run lint         # ESLint futtatása és javítás
```

#### Backend fejlesztés

```bash
php artisan serve           # Fejlesztői server
php artisan queue:listen    # Queue worker
php artisan pail           # Log viewer
php artisan migrate        # Migrációk futtatása
php artisan tinker         # REPL konzol
```

#### Tesztelés

```bash
composer test           # Tesztek futtatása
php artisan test       # Tesztek futtatása (közvetlen)
```

### Projekt Struktúra

```
onlyfix/
├── app/                    # Laravel alkalmazás logika
│   ├── Http/              # Controllers, Middleware, Requests
│   ├── Models/            # Eloquent modellek
│   └── Providers/         # Service providers
├── config/                # Konfigurációs fájlok
├── database/              # Migrációk, seeders, factories
├── resources/
│   ├── css/              # Stíluslapok
│   ├── js/               # Vue.js alkalmazás (TypeScript)
│   └── views/            # Blade templatek
├── routes/               # Útvonal definíciók
├── tests/                # Tesztek (Pest)
└── public/               # Publikus fájlok és build output
```

### Kód Minőség és Formázás

- **PHP**: Laravel Pint automatikus formázáshoz
- **JavaScript/TypeScript/Vue**: ESLint + Prettier
- **Import szervezés**: Prettier plugin
- **Tailwind osztályok**: Automatikus rendezés Prettier pluginnal
