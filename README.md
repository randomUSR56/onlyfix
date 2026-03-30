# OnlyFix

**Járműszerviz-menedzsment rendszer** | Licenc: MIT

Az OnlyFix egy nyílt forráskódú járműszerviz-menedzsment rendszer, amely digitalizálja a gépjármű-tulajdonosok, szerelők és rendszerüzemeltetők közötti kommunikációt és munkafolyamat-kezelést. Jegykezelő (ticketing) rendszert valósít meg, ahol a felhasználók CRUD műveleteket hajthatnak végre járműveikkel kapcsolatos hibajegyeken.

A rendszer célja a hagyományos papíralapú vagy telefonos szervizkezelési folyamatok kiváltása egységes, webes és natív asztali/mobil kliensről egyaránt elérhető digitális munkafelülettel.

---

## A szoftvercsomag komponensei

### OnlyFix webalkalmazás

- Laravel 12 alapú monolitikus alkalmazás
- Vue.js 3 frontend Inertia.js összekötő réteggel
- RESTful API réteg külső kliensek kiszolgálásához

### Admin MAUI kliens (külön repository: randomUSR56/Admin)

- .NET 10 MAUI alapú natív asztali és mobil kliens
- Windows, macOS, Android, iOS platformokon fut
- Kizárólag a Laravel REST API végpontjait használja
- Token alapú hitelesítés Laravel Sanctum-mal

---

## Szerepkörök

| Szerepkör | Jogosultságok |
|-----------|--------------|
| Felhasználó | Saját járművek és jegyek kezelése, szerviz előzmények |
| Szerelő | Összes jegy megtekintése, munka elfogadása, állapotok frissítése |
| Adminisztrátor | Teljes rendszer-hozzáférés, felhasználókezelés, konfiguráció |

**Jegy életciklus:** Nyitott → Kiosztott → Folyamatban → Befejezett → Lezárt

---

## Technológiai stack

### Frontend (webes kliens)

- Vue.js 3.5.13 (Composition API)
- Inertia.js v2 (@inertiajs/vue3)
- TypeScript 5.2.2
- Tailwind CSS 4.1.1
- Reka UI 2.4.1 (headless komponenskönyvtár)
- Vite 7.0.4 (HMR fejlesztői szerver)
- vue-i18n (magyar és angol nyelv)

### Backend

- PHP 8.3
- Laravel 12
- Laravel Fortify v1 (autentikáció, 2FA)
- Laravel Sanctum v4 (API token hitelesítés)
- Laravel Wayfinder v0 (route generálás)
- Spatie Laravel Permission (RBAC szerepkörkezelés)

### Adatbázis

- MySQL 8.0 (InnoDB tárolómotor)
- phpMyAdmin (webes adatbázis-adminisztráció)

### Infrastruktúra

- Docker + Docker Compose (7 konténer)
- Nginx (webszerver, reverse proxy)
- Mailpit (e-mail tesztelés, SMTP elfogás)
- Laravel Queue Worker

### Tesztelés

- Pest v4 (PHP egység és feature tesztek, böngésző tesztek)
- Vitest + Vue Test Utils (frontend egység és komponens tesztek)

### Fejlesztői eszközök

- ESLint v9, Prettier v3
- Laravel Boost MCP (AI-asszisztált fejlesztés)
- Git + GitHub (feature branch stratégia)
- Trello (Kanban alapú feladatkezelés)

---

## Docker konténerek

| Konténer | Szolgáltatás | Leírás |
|----------|-------------|--------|
| onlyfix_app | PHP-FPM | Laravel backend |
| onlyfix_nginx | Nginx | Webszerver, reverse proxy |
| onlyfix_node | Node.js 20 | Vite fejlesztői szerver |
| onlyfix_db | MySQL 8.0 | Adatbázis |
| onlyfix_mailpit | Mailpit | E-mail tesztelés |
| onlyfix_queue | PHP-FPM | Laravel Queue Worker |
| onlyfix_phpmyadmin | phpMyAdmin | Adatbázis-adminisztráció |

---

## Előfeltételek

Csak Docker szükséges – más függőség nem kell:

- Docker Desktop (Windows / macOS) vagy Docker Engine (Linux)
- Git

---

## Első indítás – fejlesztői környezet

### Unix/Linux/macOS – névfeloldással (ajánlott, sudo szükséges)

Hosts fájlba bejegyzi: `onlyfix.local`, `mailpit.onlyfix.local`, `phpmyadmin.onlyfix.local`

```bash
chmod +x scripts/unix/*.sh
bash scripts/unix/init-dev.sh
```

Elérhetőségek indítás után:

- Alkalmazás: http://onlyfix.local
- Mailpit: http://mailpit.onlyfix.local:8025
- phpMyAdmin: http://phpmyadmin.onlyfix.local:8080

### Unix/Linux/macOS – localhost (sudo nem szükséges)

Hosts fájlt nem módosítja, minden localhost-on fut.

```bash
chmod +x scripts/unix/*.sh
bash scripts/unix/init-dev-local.sh
```

Elérhetőségek indítás után:

- Alkalmazás: http://localhost
- Mailpit: http://localhost:8025
- phpMyAdmin: http://localhost:8080

### Windows – névfeloldással (rendszergazda szükséges)

```bat
scripts\windows\init-dev.bat
```

### Windows – localhost (rendszergazda nem szükséges)

```bat
scripts\windows\init-dev-local.bat
```

### Teszt fiókok (minden verzióhoz)

| Szerepkör | Email | Jelszó |
|-----------|-------|--------|
| Admin | admin@example.com | password |
| Szerelő | mechanic@example.com | password |
| Felhasználó | test@example.com | password |

---

## Production build (kiadói verzió)

### Unix/Linux/macOS – névfeloldással (sudo szükséges)

```bash
bash scripts/unix/release.sh
```

### Unix/Linux/macOS – localhost (sudo nem szükséges)

```bash
bash scripts/unix/release-local.sh
```

### Windows – névfeloldással (rendszergazda szükséges)

```bat
scripts\windows\release.bat
```

### Windows – localhost (rendszergazda nem szükséges)

```bat
scripts\windows\release-local.bat
```

A release szkriptek:

- Production módban buildelik a Docker image-eket
- `npm run build` futtatásával elkészítik az optimalizált frontend assetet
- `migrate:fresh` helyett csak `migrate` fut (adatok megmaradnak)
- Seedert nem futtatnak (éles adatokat nem írnak felül)

---

## Már deploy-olt alkalmazás futtatása

Ha a Docker image-ek már el vannak készítve és az alkalmazás egyszer már inicializálva volt, az alábbi parancsokkal indítható és állítható le anélkül, hogy az init/release szkripteket újra kellene futtatni:

**Indítás**
```bash
docker compose up -d
```

**Leállítás**
```bash
docker compose down
```

**Újraindítás**
```bash
docker compose restart
```

**Logok megtekintése**
```bash
docker compose logs -f
```

**Konténerek állapota**
```bash
docker compose ps
```

**Laravel parancsok futtatása a futó konténerben**
```bash
docker compose exec -it app php artisan migrate
docker compose exec -it app php artisan db:seed
docker compose exec -it app php artisan cache:clear
docker compose exec -it app php artisan tinker
```

**Shell a konténerben**
```bash
docker compose exec -it app bash
```

---

## Tesztek futtatása

**PHP tesztek (Pest)**
```bash
docker compose exec -it app php artisan test
```

**Frontend tesztek (Vitest)**
```bash
docker compose exec -it node npm run test:unit:run
```

**Frontend tesztek coverage riporttal**
```bash
docker compose exec -it node npm run test:coverage
```

---

## Mappastruktúra

```
.
├── .vscode/                        # VS Code beállítások
├── docker/                         # Docker konfiguráció
│   ├── logs/
│   │   ├── mysql/                  # MySQL logok
│   │   └── nginx/                  # Nginx logok
│   ├── mysql/                      # MySQL inicializáló szkriptek
│   └── nginx/                      # Nginx konfigurációs fájlok
├── onlyfix/                        # Laravel alkalmazás
│   ├── app/
│   │   ├── Http/                   # Controllers, Middleware, Requests
│   │   ├── Models/                 # Eloquent modellek
│   │   ├── Notifications/          # Értesítések
│   │   ├── Providers/              # Service Providerek
│   │   └── Services/               # Üzleti logika
│   ├── bootstrap/                  # Bootstrap, cache
│   ├── config/                     # Laravel konfiguráció
│   ├── coverage/                   # Frontend teszt coverage riportok
│   ├── database/
│   │   ├── factories/              # Model factory-k
│   │   ├── migrations/             # Adatbázis migrációk
│   │   └── seeders/                # Adatbázis seederek
│   ├── public/                     # Publikus fájlok, build output
│   ├── resources/
│   │   ├── css/                    # Stíluslapok
│   │   ├── js/                     # Vue 3 frontend forráskód
│   │   └── views/                  # Blade sablonok
│   ├── routes/                     # Web és API útvonalak
│   ├── storage/                    # Logok, feltöltött fájlok
│   └── tests/
│       ├── Feature/                # Feature tesztek
│       ├── Selenium/               # Böngésző tesztek
│       └── Unit/                   # Egység tesztek
├── scripts/
│   ├── unix/                       # Unix/Linux/macOS szkriptek
│   └── windows/                    # Windows szkriptek
├── wireframes/                     # UI wireframe-ek
│   ├── export/                     # Exportált wireframe képek
│   └── src/                        # Wireframe forrásfájlok
├── docker-compose.yml              # Docker Compose definíció
└── README.md
```

---

## Licenc

MIT License – részletek a [LICENSE](LICENSE) fájlban.
