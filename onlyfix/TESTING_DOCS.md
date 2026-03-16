# OnlyFix -- Szoftvertesztelési dokumentáció (Backend)

## 1. Bevezetés

### 1.1 A dokumentum célja

Ez a dokumentum az OnlyFix autószerviz-menedzsment rendszer backend tesztelési stratégiáját, tesztkészletét és eredményeit foglalja össze. A dokumentáció célja a szoftverminőség-biztosítás átláthatósága és a tesztlefedettség nyomon követése.

### 1.2 A rendszer rövid leírása

Az OnlyFix egy Laravel keretrendszerre épülő webalkalmazás, amely az autószerviz ügyfelei és szerelői közötti munkafolyamatot kezeli. A rendszer fő entitásai:

- **Felhasználók** (`User`) -- háromféle szerepkörrel: felhasználó, szerelő, adminisztrátor
- **Autók** (`Car`) -- felhasználókhoz rendelt járművek
- **Hibajegyek** (`Ticket`) -- szervizigények állapotkezeléssel
- **Problémák** (`Problem`) -- hibakatalógus, kategóriákba rendezve

### 1.3 Alkalmazott technológiák

| Komponens | Technológia |
|---|---|
| Backend | Laravel 12, PHP 8.3+ |
| Tesztelés | Pest PHP 3.x, SQLite in-memory |
| Jogosultságkezelés | Spatie Permission (RBAC) |
| API hitelesítés | Laravel Sanctum |
| Futtatókörnyezet | Docker (7 konténer), `make test` |

---

## 2. Tesztelési stratégia és eredmények

### 2.1 Összesítés

**386 teszteset, 1004 assertion -- mind sikeres. Futási idő: ~11 mp.**

| Kategória | Darab |
|---|---|
| Unit tesztek (modellek, értesítések) | 22 |
| API Feature tesztek (REST végpontok, üzleti logika) | ~200 |
| Web Feature tesztek (Inertia kontrollerek, útvonalak) | ~90 |
| Hitelesítési Feature tesztek | ~30 |
| Beállítások Feature tesztek | ~13 |

### 2.2 Tesztelési konfiguráció

A `phpunit.xml` izolált tesztkörnyezetet biztosít: SQLite in-memory adatbázis friss migrációval minden teszt előtt, array driver a cache-hez, session-höz és e-mailhez, szinkron sorkezelés. Minden entitáshoz factory áll rendelkezésre; a `RolePermissionSeeder` inicializálja a három szerepkört és a hozzájuk tartozó jogosultságokat.

---

## 3. Kiemelt tesztek részletesen

### 3.1 Hibajegy-munkafolyamat (`TicketWorkflowTest.php` -- 19 teszt)

A rendszer központi üzleti logikája a hibajegy teljes életciklusa: `open` -> `assigned` -> `in_progress` -> `completed` -> `closed`. Ez a tesztcsomag minden állapotátmenetet lefed, beleértve a jogosultsági szabályokat és az érvénytelen átmenetek elutasítását.

| Teszteset | Átmenet | Jogosultság |
|---|---|---|
| Szerelő elfogadhat nyitott jegyet | open -> assigned | mechanic |
| Admin elfogadhat nyitott jegyet | open -> assigned | admin |
| Felhasználó nem fogadhat el jegyet | open -> X | user |
| Már hozzárendelt jegy nem fogadható el | assigned -> X | mechanic |
| Befejezett jegy nem fogadható el | completed -> X | mechanic |
| Szerelő megkezdheti a munkát | assigned -> in_progress | mechanic |
| Csak saját jegyén kezdhet munkát | assigned (más) -> X | mechanic |
| Nem hozzárendelt jegyen nem kezdhet | open -> X | mechanic |
| Admin bármelyiken elkezdhet munkát | assigned -> in_progress | admin |
| Szerelő befejezheti a jegyet | in_progress -> completed | mechanic |
| Más szerelő jegyét nem fejezheti be | in_progress (más) -> X | mechanic |
| Nem folyamatban lévőt nem fejezhet be | assigned -> X | mechanic |
| Admin bármelyiket befejezheti | in_progress -> completed | admin |
| Tulajdonos lezárhatja a befejezett jegyet | completed -> closed | user |
| Más jegyét nem zárhatja le | completed -> X | user |
| Már lezárt jegy nem zárható le újra | closed -> X | user |
| Admin bármelyiket lezárhatja | completed -> closed | admin |
| Szerelő nem zárhat le jegyet | completed -> X | mechanic |
| Teljes munkafolyamat végigvihető | open -> ... -> closed | mind |

### 3.2 Hibajegy API (`TicketApiTest.php` -- 32 teszt)

A legnagyobb API tesztcsomag a REST CRUD-műveleteket és a munkafolyamat-végpontokat (`accept`, `start`, `complete`, `close`) teszteli mindhárom szerepkörre. Ellenőrzött forgatókönyvek:

- Nem hitelesített kérés -> 401
- Felhasználó saját jegyeit látja, másét nem (200/403)
- Jegy létrehozásához érvényes `car_id` és legalább egy `problem_id` szükséges (422)
- Felhasználó nem módosíthatja a jegy állapotát közvetlenül
- Szerelő elfogadhatja a nyitott jegyet, de ha az már hozzárendelt, nem (422)
- Statisztikai végpont: mechanic/admin -> 200, user -> 403

---

## 4. Tesztcsomagok áttekintése

### 4.1 API tesztek

| Tesztfájl | Db | Lefedett terület |
|---|---|---|
| `AuthenticationTest.php` | 18 | Regisztráció, bejelentkezés, kijelentkezés, token, profil |
| `CarApiTest.php` | 21 | Autó CRUD, jogosultságok, szűrés |
| `TicketApiTest.php` | 32 | Jegy CRUD, munkafolyamat, statisztikák |
| `ProblemApiTest.php` | 20 | Probléma CRUD, statisztikák |
| `UserApiTest.php` | 31 | Felhasználó CRUD, szerelőlista, al-erőforrások |
| `ValidationTest.php` | 18 | Kötelező mezők, egyediség, típusellenőrzés |
| `FilteringAndPaginationTest.php` | 18 | Szűrők, keresés, lapozás minden entitásra |
| `RelationshipTest.php` | 12 | Modell kapcsolatok (hasMany, belongsToMany, pivot) |
| `RolePermissionTest.php` | 10 | RBAC hierarchia, policy-k |
| `StatisticsTest.php` | 11 | Jegy- és problémastatisztikák jogosultsággal |
| `TicketStatusNotificationTest.php` | 14 | E-mail értesítések, deduplikáció |
| `TicketWorkflowTest.php` | 19 | Teljes jegy életciklus |

### 4.2 Web és egyéb tesztek

| Tesztfájl | Db | Lefedett terület |
|---|---|---|
| `WebControllersTest.php` | 51 | Inertia kontroller CRUD (autók, jegyek, problémák, felhasználók) |
| `WebRoutesTest.php` | 46 | Útvonal hozzáférés, átirányítások, szerepkör-ellenőrzés |
| `Auth/*.php` (7 fájl) | 24 | Web login, regisztráció, jelszó-reset, 2FA, e-mail verifikáció |
| `Settings/*.php` (3 fájl) | 12 | Profil és jelszó módosítás, fiók törlés, 2FA beállítások |
| `DashboardTest.php` | 2 | Dashboard hozzáférés |

### 4.3 Unit tesztek

22 teszt 5 fájlban -- modell attribútumok (`fillable`, `casts`, `hidden`), állapot-helper metódusok (`isOpen`, `isCompleted`, `isClosed`, `isInProgress`, `isAssigned`) és a `TicketStatusChanged` értesítés (csatornabeállítás, adatstruktúra).

---

## 5. API végpont-lefedettség

A rendszer OpenAPI 3.0.3 specifikációval rendelkezik (`openapi.yaml`). A tesztkészlet az összes dokumentált végpontot lefedi:

| OpenAPI tag | Végpontok | Lefedettség |
|---|---|---|
| Authentication | 4 | 100% |
| Users | 8 | 100% |
| Cars | 6 | 100% |
| Problems | 6 | 100% |
| Tickets | 10 | 100% |

Minden API végpont az alábbi szempontok szerint kerül tesztelésre:

- **Hitelesítés**: nem hitelesített kérés -> 401
- **Jogosultság**: mindhárom szerepkör elvárt viselkedése (200/201/403)
- **Validáció**: hiányzó vagy érvénytelen mezők -> 422
- **Tulajdonlás**: saját erőforrás vs. idegen erőforrás hozzáférés
- **Szűrés**: query paraméterek helyes működése
- **Állapotátmenetek**: munkafolyamat-szabályok betartása

### Nem lefedett területek

| Terület | Prioritás |
|---|---|
| Teljesítménytesztelés (load testing) | Közepes |
| API rate limiting | Közepes |
| E2E tesztelés (Cypress/Playwright) | A frontend dokumentáció tárgyalja |

---

*Dokumentum verzió: 1.0 -- 2026. március 16.*
*Tesztkeretrendszer: Pest PHP 3.x, Laravel 12.x*
