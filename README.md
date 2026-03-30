# OnlyFix

Autoszerviz hibajegy-kezelő rendszer. Felhasználók autókat regisztrálnak, hibajegyeket nyitnak, szerelők felveszik és lezárják azokat.

## Technológiai stack

- **Backend:** Laravel 12, PHP 8.2+, MySQL 8
- **Frontend:** Vue 3, Inertia.js v2, TypeScript, Tailwind CSS v4
- **Infrastruktúra:** Docker Compose (nginx, PHP-FPM, MySQL)

## Gyors indítás (Docker)

```bash
git clone https://github.com/randomUSR56/onlyfix.git
cd onlyfix
make init
```

A `make init` mindent elvégez: build, migrate, seed, npm install, kulcs generálás.

Elérhető URL-ek:
- Alkalmazás: http://onlyfix.local
- Mailpit: http://mailpit.onlyfix.local:8025
- phpMyAdmin: http://phpmyadmin.onlyfix.local:8080

## Gyors indítás (lokális)

```bash
cd onlyfix
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
composer dev
```

## Teszt fiókok

| Szerepkör   | Email                  | Jelszó     |
|-------------|------------------------|------------|
| Admin       | admin@example.com      | password   |
| Szerelő     | mechanic@example.com   | password   |
| Felhasználó | test@example.com       | password   |

## Mappastruktúra

```
.
├── docker/              # Docker konfiguráció (nginx, mysql, PHP)
├── docker-compose.yml   # Docker Compose definíció
├── scripts/             # Segéd scriptek
├── Makefile             # make init, make up, make down, stb.
└── onlyfix/             # Laravel alkalmazás
    ├── app/             # Controllers, Models, Services
    ├── resources/js/    # Vue 3 frontend
    ├── routes/          # Web és API útvonalak
    ├── database/        # Migrációk, seederek, factory-k
    └── public/          # Publikus fájlok
```

## API dokumentáció

- OpenAPI specifikáció: [onlyfix/openapi.yaml](onlyfix/openapi.yaml)
- Postman collection: [onlyfix/postman_collection.json](onlyfix/postman_collection.json)

## Licenc

MIT
