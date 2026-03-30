# OnlyFix – Laravel alkalmazás

Ez a mappa tartalmazza a Laravel 12 alkalmazást.

## Főbb mappák

| Mappa            | Leírás                                              |
|------------------|-----------------------------------------------------|
| `app/`           | Controllers, Models, Services, Notifications        |
| `resources/js/`  | Vue 3 frontend (Inertia.js oldalak, komponensek)    |
| `routes/`        | Web és API útvonalak                                |
| `database/`      | Migrációk, seederek, factory-k                      |
| `config/`        | Laravel konfigurációs fájlok                        |
| `tests/`         | Pest tesztek                                        |
| `public/`        | Publikus fájlok, build output                       |

## Fejlesztési parancsok

```bash
# Teljes dev szerver indítása (backend + frontend + queue + log)
composer dev

# Csak frontend dev szerver
npm run dev

# Production build
npm run build

# Tesztek futtatása
php artisan test

# Adatbázis újraépítése tesztadatokkal
php artisan migrate:fresh --seed

# Kód formázás
./vendor/bin/pint          # PHP
npm run format             # JS/TS/Vue
```

## Környezeti változók

A `.env.example` fájl tartalmazza az összes szükséges változót. Másolat készítése:

```bash
cp .env.example .env
php artisan key:generate
```
