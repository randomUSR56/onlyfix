# OnlyFix – Szoftvertesztelési Dokumentáció

## 1. Bevezetés

### 1.1 A dokumentum célja

Ez a dokumentum az OnlyFix autószerviz-menedzsment rendszer backend tesztelési stratégiáját, tesztkészletét és eredményeit foglalja össze. A dokumentáció célja a szoftverminőség-biztosítás átláthatósága és a tesztlefedettség nyomon követése.

### 1.2 A rendszer rövid leírása

Az OnlyFix egy Laravel keretrendszerre épülő webalkalmazás, amely autószerviz ügyfelek és szerelők közötti munkafolyamatot kezeli. A rendszer fő entitásai:

- **Felhasználók** (`User`) – háromféle szerepkörrel: felhasználó, szerelő, adminisztrátor
- **Autók** (`Car`) – felhasználókhoz rendelt járművek
- **Hibajegyek** (`Ticket`) – szervizigények, állapotkezeléssel
- **Problémák** (`Problem`) – hibakatológus, kategóriákba rendezve

### 1.3 Alkalmazott technológiák

| Komponens | Technológia |
|---|---|
| Backend keretrendszer | Laravel 12 |
| Tesztelési keretrendszer | Pest PHP |
| Adatbázis (teszt) | SQLite (memóriában) |
| Jogosultságkezelés | Spatie Permission |
| API hitelesítés | Laravel Sanctum |
| Frontend (Inertia) | Vue 3 + Inertia.js |
| Futtatókörnyezet | Docker (7 konténer) |

### 1.4 Teszt futtatása

```bash
# Docker konténerben futtatás a Makefile-on keresztül
make test

# Közvetlen futtatás a konténerben
docker compose exec app php artisan test

# Egy adott tesztfájl futtatása
docker compose exec app php artisan test --filter=AuthenticationTest
```

---

## 2. Tesztelési stratégia

### 2.1 Tesztelési szintek

A projekt az alábbi tesztelési szinteket alkalmazza:

| Szint | Leírás | Tesztek száma |
|---|---|---|
| Unit tesztek | Modellek, értékek, attribútumok, értesítések izolált tesztelése | 22 |
| Feature tesztek (API) | REST API végpontok és üzleti logika integrációs tesztelése | ~200 |
| Feature tesztek (Web) | Inertia web kontroller és útvonal tesztelés | ~90 |
| Feature tesztek (Auth) | Hitelesítési és jogosultsági folyamatok tesztelése | ~30 |
| Feature tesztek (Beállítások) | Felhasználói beállítások tesztelése | ~13 |

**Összesen: 386 teszteset, 1004 assertion**

### 2.2 Tesztelési konfiguráció

A tesztkörnyezet konfigurációja (`phpunit.xml`):

- **Adatbázis**: SQLite in-memory (`:memory:`) – gyors, izolált, minden teszt előtt friss migráció
- **Cache**: Array driver – nincs perzisztens cache a tesztek között
- **E-mail**: Array driver – e-mailek elfogása tesztelési célokra
- **Session**: Array driver – nincs perzisztens munkamenet
- **Sor**: Sync driver – azonnali végrehajtás, nincs aszinkron feldolgozás

### 2.3 Teszt adatkezelés

- **Factory-k**: Minden entitáshoz (`User`, `Car`, `Ticket`, `Problem`) gyártóosztályok állnak rendelkezésre
- **Seeder-ek**: `RolePermissionSeeder` biztosítja a szerepkörök és jogosultságok inicializálását
- **RefreshDatabase**: Minden Feature teszt előtt friss adatbázis-állapot

---

## 3. Tesztesetek részletes leírása

### 3.1 Unit tesztek

#### 3.1.1 Ticket modell tesztek (`tests/Unit/ExampleTest.php`)

| # | Teszteset | Leírás | Elvárt eredmény |
|---|---|---|---|
| U01 | `isOpen` állapot | Nyitott jegy `isOpen()` metódusa | `true` |
| U02 | `isCompleted` állapot | Befejezett jegy `isCompleted()` metódusa | `true` |
| U03 | `isClosed` állapot | Lezárt jegy `isClosed()` metódusa | `true` |
| U04 | `isInProgress` állapot | Folyamatban lévő jegy `isInProgress()` metódusa  | `true` |
| U05 | `isAssigned` igaz | Szerelőhöz rendelt jegy | `true` |
| U06 | `isAssigned` hamis | Nem rendelt jegy | `false` |
| U07 | `accepted_at` cast | DateTime típusú mező | `datetime` |
| U08 | `completed_at` cast | DateTime típusú mező | `datetime` |
| U09 | Kitölthető mezők | Ticket fillable attribútumok | Mind jelen van |

#### 3.1.2 Car modell tesztek (`tests/Unit/CarModelTest.php`)

| # | Teszteset | Leírás | Elvárt eredmény |
|---|---|---|---|
| U10 | Kitölthető mezők | `user_id`, `make`, `model`, `year`, `license_plate`, `vin`, `color` | Mind jelen van |
| U11 | Táblanév | `cars` tábla használata | `cars` |

#### 3.1.3 Problem modell tesztek (`tests/Unit/ProblemModelTest.php`)

| # | Teszteset | Leírás | Elvárt eredmény |
|---|---|---|---|
| U12 | Kitölthető mezők | `name`, `category`, `description`, `is_active` | Mind jelen van |
| U13 | `is_active` cast | Boolean típusra öntés | `boolean` |
| U14 | Táblanév | `problems` tábla használata | `problems` |

#### 3.1.4 User modell tesztek (`tests/Unit/UserModelTest.php`)

| # | Teszteset | Leírás | Elvárt eredmény |
|---|---|---|---|
| U15 | Kitölthető mezők | `name`, `email`, `password` | Mind jelen van |
| U16 | Rejtett mezők | `password`, `two_factor_secret`, stb. | Mind rejtett |
| U17 | `email_verified_at` cast | DateTime típusra öntés | `datetime` |
| U18 | `password` cast | Hash típusra öntés | `hashed` |
| U19 | Táblanév | `users` tábla használata | `users` |

#### 3.1.5 TicketStatusChanged értesítés tesztek (`tests/Unit/TicketStatusChangedNotificationTest.php`)

| # | Teszteset | Leírás | Elvárt eredmény |
|---|---|---|---|
| U20 | Adatok tárolása | Értesítés tárolja a jegy és állapot adatokat | Helyes értékek |
| U21 | Kézbesítési csatorna | Mail csatornán keresztül küld | `['mail']` |
| U22 | Array reprezentáció | `toArray()` visszatérési értéke | `ticket_id`, `old_status`, `new_status` |

---

### 3.2 Hitelesítési tesztek (Authentication)

#### 3.2.1 API hitelesítés (`tests/Feature/AuthenticationTest.php`)

| # | Teszteset | Leírás | HTTP | Státusz |
|---|---|---|---|---|
| A01 | Regisztráció érvényes adatokkal | Felhasználó létrehozása | POST `/api/register` | 201 |
| A02 | Regisztráció név nélkül | Validációs hiba | POST `/api/register` | 422 |
| A03 | Regisztráció érvénytelen e-maillel | Validációs hiba | POST `/api/register` | 422 |
| A04 | Regisztráció nem egyedi e-maillel | Validációs hiba | POST `/api/register` | 422 |
| A05 | Regisztráció jelszó-megerősítés nélkül | Validációs hiba | POST `/api/register` | 422 |
| A06 | Regisztráció eltérő jelszó-megerősítés | Validációs hiba | POST `/api/register` | 422 |
| A07 | Új felhasználók `user` szerepkört kapnak | Automatikus szerepkör | POST `/api/register` | 201 |
| A08 | Bejelentkezés érvényes adatokkal | Token visszaadása | POST `/api/login` | 200 |
| A09 | Bejelentkezés érvénytelen adatokkal | Hibás e-mail | POST `/api/login` | 422 |
| A10 | Bejelentkezés rossz jelszóval | Hibás jelszó | POST `/api/login` | 422 |
| A11 | Bejelentkezés e-mail nélkül | Validációs hiba | POST `/api/login` | 422 |
| A12 | Bejelentkezés jelszó nélkül | Validációs hiba | POST `/api/login` | 422 |
| A13 | Kijelentkezés hitelesített felhasználóval | Sikeres kijelentkezés | POST `/api/logout` | 200 |
| A14 | Kijelentkezés hitelesítés nélkül | Jogosulatlan | POST `/api/logout` | 401 |
| A15 | Kijelentkezés érvényteleníti a tokent | Token törlése | POST `/api/logout` | 200 |
| A16 | Profil lekérése hitelesítve | Felhasználói adatok | GET `/api/user` | 200 |
| A17 | Profil lekérése hitelesítés nélkül | Jogosulatlan | GET `/api/user` | 401 |
| A18 | Profil tartalmazza a szerepköröket | Roles mező jelen | GET `/api/user` | 200 |

#### 3.2.2 Web hitelesítés (`tests/Feature/Auth/`)

| # | Teszteset | Fájl | Leírás |
|---|---|---|---|
| WA01 | Bejelentkezési oldal megjelenítése | AuthenticationTest | Login form renderelés |
| WA02 | Bejelentkezés a webes felületen | AuthenticationTest | Sikeres web login |
| WA03 | 2FA átirányítás | AuthenticationTest | Kétfaktoros hitelesítésre irányít |
| WA04 | Rossz jelszóval nem enged be | AuthenticationTest | Hibás jelszó |
| WA05 | Kijelentkezés webes felületen | AuthenticationTest | Sikeres web logout |
| WA06 | Túl sok próbálkozás | AuthenticationTest | Rate limiter működése |
| WA07 | E-mail ellenőrzési oldal | EmailVerificationTest | Verification form |
| WA08 | E-mail ellenőrzés | EmailVerificationTest | Sikeres verify |
| WA09 | Érvénytelen hash | EmailVerificationTest | Elutasítás |
| WA10 | Érvénytelen user ID | EmailVerificationTest | Elutasítás |
| WA11 | Már ellenőrzött felhasználó | EmailVerificationTest | Átirányítás |
| WA12 | Jelszó-megerősítő oldal | PasswordConfirmationTest | Form renderelés |
| WA13 | Jelszó-megerősítés hitelesítést igényel | PasswordConfirmationTest | Redirect login |
| WA14 | Jelszó-visszaállítási link oldal | PasswordResetTest | Form renderelés |
| WA15 | Jelszó-visszaállítási link küldése | PasswordResetTest | E-mail küldés |
| WA16 | Jelszó-visszaállítási oldal | PasswordResetTest | Reset form |
| WA17 | Jelszó visszaállítása érvényes tokennel | PasswordResetTest | Sikeres reset |
| WA18 | Jelszó visszaállítása érvénytelen tokennel | PasswordResetTest | Elutasítás |
| WA19 | Regisztrációs oldal megjelenítése | RegistrationTest | Form renderelés |
| WA20 | Új felhasználó regisztráció | RegistrationTest | Sikeres regisztráció |
| WA21 | 2FA kihívás átirányít ha nincs bejelentkezve | TwoFactorChallengeTest | Redirect |
| WA22 | 2FA kihívás renderelése | TwoFactorChallengeTest | Form megjelenés |
| WA23 | Verifikációs értesítés küldése | VerificationNotificationTest | E-mail küldés |
| WA24 | Nem küld ha már ellenőrzött | VerificationNotificationTest | Nincs küldés |

---

### 3.3 Autó (Car) API tesztek (`tests/Feature/CarApiTest.php`)

| # | Teszteset | HTTP | Elvárt | Szerepkör |
|---|---|---|---|---|
| C01 | Nem hitelesített felhasználó nem fér hozzá | GET `/api/cars` | 401 | - |
| C02 | Felhasználó saját autóit látja | GET `/api/cars` | 200 | user |
| C03 | Szerelő minden autót lát | GET `/api/cars` | 200 | mechanic |
| C04 | Admin minden autót lát | GET `/api/cars` | 200 | admin |
| C05 | Autók szűrhetők `user_id` alapján | GET `/api/cars?user_id=X` | 200 | admin |
| C06 | Felhasználó autót hozhat létre magának | POST `/api/cars` | 201 | user |
| C07 | Admin autót hozhat létre más felhasználónak | POST `/api/cars` | 201 | admin |
| C08 | Felhasználó nem hozhat létre autót másnak | POST `/api/cars` | 403 | user |
| C09 | Kötelező mezők validálása | POST `/api/cars` | 422 | user |
| C10 | Rendszám egyediségének ellenőrzése | POST `/api/cars` | 422 | user |
| C11 | Felhasználó saját autóját megtekintheti | GET `/api/cars/{id}` | 200 | user |
| C12 | Felhasználó más autóját nem tekintheti meg | GET `/api/cars/{id}` | 403 | user |
| C13 | Szerelő bármely autót megtekintheti | GET `/api/cars/{id}` | 200 | mechanic |
| C14 | Felhasználó saját autóját módosíthatja | PUT `/api/cars/{id}` | 200 | user |
| C15 | Felhasználó más autóját nem módosíthatja | PUT `/api/cars/{id}` | 403 | user |
| C16 | Admin bármely autót módosíthatja | PUT `/api/cars/{id}` | 200 | admin |
| C17 | Felhasználó saját autóját törölheti | DELETE `/api/cars/{id}` | 200 | user |
| C18 | Felhasználó más autóját nem törölheti | DELETE `/api/cars/{id}` | 403 | user |
| C19 | Admin bármely autót törölheti | DELETE `/api/cars/{id}` | 200 | admin |
| C20 | Felhasználó autójának jegyeit megtekintheti | GET `/api/cars/{id}/tickets` | 200 | user |
| C21 | Más autójának jegyeit nem tekintheti meg | GET `/api/cars/{id}/tickets` | 403 | user |

---

### 3.4 Hibajegy (Ticket) API tesztek (`tests/Feature/TicketApiTest.php`)

| # | Teszteset | HTTP | Elvárt | Szerepkör |
|---|---|---|---|---|
| T01 | Nem hitelesített nem fér hozzá | GET `/api/tickets` | 401 | - |
| T02 | Felhasználó saját jegyeit látja | GET `/api/tickets` | 200 | user |
| T03 | Szerelő minden jegyet lát | GET `/api/tickets` | 200 | mechanic |
| T04 | Jegyek szűrhetők állapot szerint | GET `/api/tickets?status=open` | 200 | mechanic |
| T05 | Jegyek szűrhetők prioritás szerint | GET `/api/tickets?priority=high` | 200 | mechanic |
| T06 | Felhasználó jegyet hozhat létre saját autójára | POST `/api/tickets` | 201 | user |
| T07 | Más felhasználó autójára nem hozhat létre | POST `/api/tickets` | 403 | user |
| T08 | Kötelező mezők validálása | POST `/api/tickets` | 422 | user |
| T09 | Legalább egy probléma szükséges | POST `/api/tickets` | 422 | user |
| T10 | Felhasználó saját jegyét megtekintheti | GET `/api/tickets/{id}` | 200 | user |
| T11 | Más jegyét nem tekintheti meg | GET `/api/tickets/{id}` | 403 | user |
| T12 | Szerelő bármely jegyet megtekintheti | GET `/api/tickets/{id}` | 200 | mechanic |
| T13 | Felhasználó saját nyitott jegyét módosíthatja | PUT `/api/tickets/{id}` | 200 | user |
| T14 | Nem nyitott jegyét nem módosíthatja | PUT `/api/tickets/{id}` | 403 | user |
| T15 | Szerelő bármely jegyet módosíthatja | PUT `/api/tickets/{id}` | 200 | mechanic |
| T16 | Felhasználó nem változtathatja az állapotot | PUT `/api/tickets/{id}` | - | user |
| T17 | Felhasználó saját nyitott jegyét törölheti | DELETE `/api/tickets/{id}` | 200 | user |
| T18 | Nem nyitott jegyét nem törölheti | DELETE `/api/tickets/{id}` | 403 | user |
| T19 | Admin bármely jegyet törölheti | DELETE `/api/tickets/{id}` | 200 | admin |

#### Jegy munkafolyamat tesztek

| # | Teszteset | HTTP | Elvárt | Szerepkör |
|---|---|---|---|---|
| T20 | Szerelő elfogadhatja a nyitott jegyet | POST `/api/tickets/{id}/accept` | 200 | mechanic |
| T21 | Felhasználó nem fogadhat el jegyet | POST `/api/tickets/{id}/accept` | 403 | user |
| T22 | Már hozzárendelt jegy nem fogadható el | POST `/api/tickets/{id}/accept` | 422 | mechanic |
| T23 | Szerelő elkezdheti a munkát saját jegyén | POST `/api/tickets/{id}/start` | 200 | mechanic |
| T24 | Más szerelő jegyén nem kezdhet munkát | POST `/api/tickets/{id}/start` | 403 | mechanic |
| T25 | Admin bármelyik jegyen elkezdhet munkát | POST `/api/tickets/{id}/start` | 200 | admin |
| T26 | Szerelő befejezheti saját jegyét | POST `/api/tickets/{id}/complete` | 200 | mechanic |
| T27 | Más szerelő jegyét nem fejezheti be | POST `/api/tickets/{id}/complete` | 403 | mechanic |
| T28 | Felhasználó lezárhatja saját jegyét | POST `/api/tickets/{id}/close` | 200 | user |
| T29 | Más jegyét nem zárhatja le | POST `/api/tickets/{id}/close` | 403 | user |
| T30 | Admin bármely jegyet lezárhatja | POST `/api/tickets/{id}/close` | 200 | admin |
| T31 | Szerelő megnézheti a statisztikákat | GET `/api/tickets/statistics` | 200 | mechanic |
| T32 | Felhasználó nem nézheti a statisztikákat | GET `/api/tickets/statistics` | 403 | user |

---

### 3.5 Probléma (Problem) API tesztek (`tests/Feature/ProblemApiTest.php`)

| # | Teszteset | HTTP | Elvárt | Szerepkör |
|---|---|---|---|---|
| P01 | Nem hitelesített nem fér hozzá | GET `/api/problems` | 401 | - |
| P02 | Hitelesített felhasználó nézheti a problémákat | GET `/api/problems` | 200 | user |
| P03 | Szűrés kategória alapján | GET `/api/problems?category=engine` | 200 | user |
| P04 | Szűrés aktív állapot szerint | GET `/api/problems?is_active=1` | 200 | user |
| P05 | Keresés név alapján | GET `/api/problems?search=oil` | 200 | user |
| P06 | Szerelő problémát hozhat létre | POST `/api/problems` | 201 | mechanic |
| P07 | Admin problémát hozhat létre | POST `/api/problems` | 201 | admin |
| P08 | Felhasználó nem hozhat létre problémát | POST `/api/problems` | 403 | user |
| P09 | Kötelező mezők validálása | POST `/api/problems` | 422 | mechanic |
| P10 | Problémanév egyediségének ellenőrzése | POST `/api/problems` | 422 | mechanic |
| P11 | Bármely hitelesített felhasználó megtekinthet | GET `/api/problems/{id}` | 200 | user |
| P12 | Szerelő módosíthat problémát | PUT `/api/problems/{id}` | 200 | mechanic |
| P13 | Admin módosíthat problémát | PUT `/api/problems/{id}` | 200 | admin |
| P14 | Felhasználó nem módosíthat problémát | PUT `/api/problems/{id}` | 403 | user |
| P15 | Admin törölhet problémát | DELETE `/api/problems/{id}` | 200 | admin |
| P16 | Szerelő nem törölhet problémát | DELETE `/api/problems/{id}` | 403 | mechanic |
| P17 | Felhasználó nem törölhet problémát | DELETE `/api/problems/{id}` | 403 | user |
| P18 | Szerelő nézheti a problémastatisztikákat | GET `/api/problems/statistics` | 200 | mechanic |
| P19 | Admin nézheti a problémastatisztikákat | GET `/api/problems/statistics` | 200 | admin |
| P20 | Felhasználó nem nézheti a statisztikákat | GET `/api/problems/statistics` | 403 | user |

---

### 3.6 Felhasználó (User) API tesztek (`tests/Feature/UserApiTest.php`)

| # | Teszteset | HTTP | Elvárt | Szerepkör |
|---|---|---|---|---|
| FH01 | Nem hitelesített nem fér hozzá | GET `/api/users` | 401 | - |
| FH02 | Admin láthatja az összes felhasználót | GET `/api/users` | 200 | admin |
| FH03 | Felhasználó nem láthatja az összeset | GET `/api/users` | 403 | user |
| FH04 | Felhasználók szűrhetők szerepkör szerint | GET `/api/users?role=mechanic` | 200 | admin |
| FH05 | Felhasználók kereshetők | GET `/api/users?search=john` | 200 | admin |
| FH06 | Admin létrehozhat felhasználót | POST `/api/users` | 201 | admin |
| FH07 | Felhasználó nem hozhat létre felhasználót | POST `/api/users` | 403 | user |
| FH08 | Kötelező mezők validálása | POST `/api/users` | 422 | admin |
| FH09 | E-mail egyediségének ellenőrzése | POST `/api/users` | 422 | admin |
| FH10 | Felhasználó megtekintheti saját profilját | GET `/api/users/{id}` | 200 | user |
| FH11 | Más profilját nem tekintheti meg | GET `/api/users/{id}` | 403 | user |
| FH12 | Admin bármely profilt megtekintheti | GET `/api/users/{id}` | 200 | admin |
| FH13 | Felhasználó módosíthatja saját profilját | PUT `/api/users/{id}` | 200 | user |
| FH14 | Más profilját nem módosíthatja | PUT `/api/users/{id}` | 403 | user |
| FH15 | Admin bármely profilt módosíthatja | PUT `/api/users/{id}` | 200 | admin |
| FH16 | Felhasználó nem változtathatja saját szerepkörét | PUT `/api/users/{id}` | 403 | user |
| FH17 | Admin megváltoztathatja a szerepkört | PUT `/api/users/{id}` | 200 | admin |
| FH18 | Jelszó hash-elten tárolódik | PUT `/api/users/{id}` | 200 | user |
| FH19 | Admin törölhet felhasználót | DELETE `/api/users/{id}` | 200 | admin |
| FH20 | Admin nem törölheti önmagát | DELETE `/api/users/{id}` | 403 | admin |
| FH21 | Felhasználó nem törölhet felhasználót | DELETE `/api/users/{id}` | 403 | user |
| FH22 | Saját profil lekérése `/users/me` | GET `/api/users/me` | 200 | user |
| FH23 | Szerelőlista megtekintése (szerelő) | GET `/api/users/mechanics` | 200 | mechanic |
| FH24 | Szerelőlista megtekintése (admin) | GET `/api/users/mechanics` | 200 | admin |
| FH25 | Felhasználó nem éri el a szerelőlistát | GET `/api/users/mechanics` | 403 | user |
| FH26 | Saját jegyek megtekintése | GET `/api/users/{id}/tickets` | 200 | user |
| FH27 | Más jegyeinek megtekintése (szerelő) | GET `/api/users/{id}/tickets` | 200 | mechanic |
| FH28 | Más jegyeinek megtekintése tiltva | GET `/api/users/{id}/tickets` | 403 | user |
| FH29 | Saját autók megtekintése | GET `/api/users/{id}/cars` | 200 | user |
| FH30 | Más autóinak megtekintése (szerelő) | GET `/api/users/{id}/cars` | 200 | mechanic |
| FH31 | Más autóinak megtekintése tiltva | GET `/api/users/{id}/cars` | 403 | user |

---

### 3.7 Validációs tesztek (`tests/Feature/ValidationTest.php`)

| # | Teszteset | Entitás | Validált mező |
|---|---|---|---|
| V01 | Autó – `make` kötelező | Car | make |
| V02 | Autó – `model` kötelező | Car | model |
| V03 | Autó – `year` érvényes szám | Car | year |
| V04 | Autó – `license_plate` egyedi | Car | license_plate |
| V05 | Autó – `vin` egyedi (ha megadott) | Car | vin |
| V06 | Jegy – `car_id` kötelező | Ticket | car_id |
| V07 | Jegy – `car_id` érvényes legyen | Ticket | car_id |
| V08 | Jegy – `description` kötelező | Ticket | description |
| V09 | Jegy – `priority` érvényes érték | Ticket | priority |
| V10 | Jegy – `problem_ids` érvényes legyen | Ticket | problem_ids |
| V11 | Probléma – `name` kötelező | Problem | name |
| V12 | Probléma – `category` kötelező | Problem | category |
| V13 | Probléma – `category` érvényes érték | Problem | category |
| V14 | Probléma – `name` egyedi | Problem | name |
| V15 | Felhasználó – `name` kötelező | User | name |
| V16 | Felhasználó – `email` érvényes | User | email |
| V17 | Felhasználó – `email` egyedi | User | email |
| V18 | Felhasználó – email egyediség módosításnál | User | email |

---

### 3.8 Szűrés és lapozás tesztek (`tests/Feature/FilteringAndPaginationTest.php`)

| # | Teszteset | Entitás | Szűrő |
|---|---|---|---|
| SZ01 | Jegyek szűrése állapot szerint | Ticket | status |
| SZ02 | Jegyek szűrése prioritás szerint | Ticket | priority |
| SZ03 | Jegyek szűrése user_id szerint | Ticket | user_id |
| SZ04 | Jegyek szűrése car_id szerint | Ticket | car_id |
| SZ05 | Jegyek szűrése mechanic_id szerint | Ticket | mechanic_id |
| SZ06 | Több szűrő kombinálása | Ticket | status + priority |
| SZ07 | Problémák szűrése kategória szerint | Problem | category |
| SZ08 | Problémák szűrése aktív állapot szerint | Problem | is_active |
| SZ09 | Problémák keresése név alapján | Problem | search |
| SZ10 | Autók szűrése user_id szerint | Car | user_id |
| SZ11 | Autók keresése gyártó alapján | Car | make |
| SZ12 | Autók keresése rendszám alapján | Car | license_plate |
| SZ13 | Felhasználók szűrése szerepkör szerint | User | role |
| SZ14 | Felhasználók keresése név alapján | User | name |
| SZ15 | Felhasználók keresése e-mail alapján | User | email |
| SZ16 | Eredmények lapozása alapértelmezetten | - | per_page |
| SZ17 | `per_page` paraméter vezérli az eredményeket | - | per_page |
| SZ18 | Lapozás szűrőkkel együtt működik | - | szűrő + per_page |

---

### 3.9 Modell kapcsolat tesztek (`tests/Feature/RelationshipTest.php`)

| # | Teszteset | Kapcsolat |
|---|---|---|
| K01 | Jegyekhez több probléma rendelhető | Ticket → Problems (N:M) |
| K02 | Jegy-probléma kapcsolatban megjegyzés lehet | Ticket ↔ Problem pivot notes |
| K03 | Jegyek problémákkal együtt lekérdezhetők | Ticket with problems |
| K04 | Jegy tartalmazza az autó adatait | Ticket → Car |
| K05 | Jegy tartalmazza a felhasználó adatait | Ticket → User |
| K06 | Jegy tartalmazza a szerelő adatait | Ticket → Mechanic |
| K07 | Autó jegyei lekérdezhetők | Car → Tickets |
| K08 | Autó jegyei tartalmazzák a problémákat | Car → Tickets → Problems |
| K09 | Felhasználó autói lekérdezhetők | User → Cars |
| K10 | Felhasználó jegyei lekérdezhetők | User → Tickets |
| K11 | Szerelő hozzárendelt jegyei lekérdezhetők | User (mechanic) → assignedTickets |
| K12 | Probléma jegyei nyomon követhetők | Problem → Tickets |

---

### 3.10 Szerepkör és jogosultság tesztek (`tests/Feature/RolePermissionTest.php`)

| # | Teszteset | Leírás |
|---|---|---|
| J01 | Felhasználóhoz szerepkör rendelhető | assignRole működése |
| J02 | Admin rendelkezik minden jogosultsággal | Teljes hozzáférés |
| J03 | Szerelő rendelkezik jegykezelési jogokkal | Jegyek kezelése |
| J04 | Felhasználó korlátozott jogosultságokkal rendelkezik | Csak saját erőforrások |
| J05 | Felhasználó nem láthatja az összes felhasználót | Elérés tiltva |
| J06 | Admin láthatja az összes felhasználót | Hozzáférés engedélyezve |
| J07 | Admin nem törölheti saját magát | Önvédelmi szabály |
| J08 | Felhasználó módosíthatja saját profilját | Saját profil |
| J09 | Felhasználó nem módosíthatja más profilját | Más profil tiltva |
| J10 | Szerepkörök helyes hierarchiát mutatnak | admin > mechanic > user |

---

### 3.11 Statisztikai tesztek (`tests/Feature/StatisticsTest.php`)

| # | Teszteset | Végpont | Vizsgált adat |
|---|---|---|---|
| ST01 | Szerelő megtekintheti a jegystatisztikákat | `/api/tickets/statistics` | Állapot szerinti bontás |
| ST02 | Admin megtekintheti a jegystatisztikákat | `/api/tickets/statistics` | Állapot szerinti bontás |
| ST03 | Felhasználó nem tekintheti meg | `/api/tickets/statistics` | 403 |
| ST04 | Pontos darabszámok állapot szerint | `/api/tickets/statistics` | Számértékek |
| ST05 | Prioritás szerinti bontás | `/api/tickets/statistics` | Prioritás statisztika |
| ST06 | Szerelő megtekintheti a problémastatisztikákat | `/api/problems/statistics` | Kategória bontás |
| ST07 | Admin megtekintheti a problémastatisztikákat | `/api/problems/statistics` | Kategória bontás |
| ST08 | Felhasználó nem tekintheti meg | `/api/problems/statistics` | 403 |
| ST09 | Kategória szerinti bontás | `/api/problems/statistics` | Kategóriánként |
| ST10 | Aktív/inaktív megkülönböztetés | `/api/problems/statistics` | Aktív állapot |
| ST11 | Szerelő munkaterhelés nyomon követése | `/api/tickets/statistics` | Mechanic workload |

---

### 3.12 Értesítési tesztek (`tests/Feature/TicketStatusNotificationTest.php`)

| # | Teszteset | Művelet | Címzett |
|---|---|---|---|
| E01 | Jegy elfogadása értesíti a tulajdonost | accept | user (owner) |
| E02 | Elfogadás nem értesíti a szerelőt | accept | mechanic (nem kap) |
| E03 | Munka megkezdése értesíti a tulajdonost | start | user (owner) |
| E04 | Befejezés értesíti a tulajdonost | complete | user (owner) |
| E05 | Lezárás értesíti a szerelőt | close | mechanic |
| E06 | Admin lezárás értesítéseket küld | close (admin) | mechanic + user |
| E07 | Állapotváltozás értesít | update → assigned | user |
| E08 | Állapotváltozás nélküli módosítás nem értesít | update (no status change) | senki |
| E09 | In_progress állapot értesíti a tulajdonost | update → in_progress | user |
| E10 | Admin-tulajdonos deduplikáció | close (admin=owner) | Nem küld duplán |
| E11 | Több admin nem kap dupla értesítést | action | Admin-ok egyenként |
| E12 | Értesítés helyes tartalommal | accept | subject, body |
| E13 | E-mail helyes struktúrával | all transitions | mail fields |
| E14 | Array tartalom helyes struktúrával | all transitions | ticket_id, statuses |

---

### 3.13 Jegy munkafolyamat tesztek (`tests/Feature/TicketWorkflowTest.php`)

A teljes jegy életciklus tesztelése: `open → assigned → in_progress → completed → closed`

| # | Teszteset | Átmenet | Jogosultság |
|---|---|---|---|
| MF01 | Szerelő elfogadhat nyitott jegyet | open → assigned | mechanic |
| MF02 | Admin elfogadhat nyitott jegyet | open → assigned | admin |
| MF03 | Felhasználó nem fogadhat el jegyet | open → ✗ | user |
| MF04 | Már hozzárendelt jegy nem fogadható el | assigned → ✗ | mechanic |
| MF05 | Befejezett jegy nem fogadható el | completed → ✗ | mechanic |
| MF06 | Szerelő megkezdheti a munkát | assigned → in_progress | mechanic |
| MF07 | Csak saját jegyén kezdhet munkát | assigned (más) → ✗ | mechanic |
| MF08 | Nem hozzárendelt jegyen nem kezdhet | open → ✗ start | mechanic |
| MF09 | Admin bármelyiken elkezdhet munkát | assigned → in_progress | admin |
| MF10 | Szerelő befejezheti a jegyet | in_progress → completed | mechanic |
| MF11 | Más szerelő jegyét nem fejezheti be | in_progress (más) → ✗ | mechanic |
| MF12 | Nem folyamatban lévőt nem fejezhet be | assigned → ✗ complete | mechanic |
| MF13 | Admin bármelyiket befejezheti | in_progress → completed | admin |
| MF14 | Tulajdonos lezárhatja a befejezett jegyet | completed → closed | user |
| MF15 | Más jegyét nem zárhatja le | completed → ✗ | user |
| MF16 | Már lezárt jegy nem zárható le újra | closed → ✗ | user |
| MF17 | Admin bármelyiket lezárhatja | completed → closed | admin |
| MF18 | Szerelő nem zárhat le jegyet | completed → ✗ | mechanic |
| MF19 | Teljes munkafolyamat végigvihető | open → ... → closed | mind |

---

### 3.14 Web kontroller tesztek (`tests/Feature/WebControllersTest.php`)

| # | Csoport | Tesztesetek száma | Leírás |
|---|---|---|---|
| WC01-WC11 | Autók | 11 | CRUD műveletek, Inertia válaszok, jogosultságok |
| WC12-WC23 | Jegyek | 12 | CRUD, elfogadás, megkezdés, befejezés, lezárás |
| WC24-WC34 | Problémák | 11 | CRUD, statisztikák, szerepkör-alapú hozzáférés |
| WC35-WC48 | Felhasználók | 14 | CRUD, szerepkör-kezelés, szerelőlista |
| WC49-WC51 | Statisztikák | 3 | Jegy- és problémastatisztikák hozzáférése |

---

### 3.15 Web útvonal tesztek (`tests/Feature/WebRoutesTest.php`)

| # | Csoport | Tesztesetek száma | Leírás |
|---|---|---|---|
| WR01 | Nyilvános | 2 | Kezdőlap átirányítás, hitelesítés szükséges |
| WR02 | Dashboard | 1 | Hitelesített felhasználó dashboard hozzáférés |
| WR03 | Autók | 8 | Autó útvonalak elérhetősége és jogosultságok |
| WR04 | Jegyek | 11 | Jegy útvonalak, beleértve munkafolyamat lépéseket |
| WR05 | Problémák | 7 | Probléma útvonalak szerepkör-alapú hozzáféréssel |
| WR06 | Felhasználók | 5 | Admin-korlátozott felhasználókezelés |
| WR07 | Statisztikák | 5 | Statisztika útvonalak hozzáférés-vezérlése |
| WR08 | Szerelők | 3 | Szerelőlista útvonal hozzáférés |
| WR09 | Hitelesítés kötelező | 4 | Hitelesítetlen átirányítás |

---

### 3.16 Beállítások tesztek (`tests/Feature/Settings/`)

#### Jelszó módosítás (`PasswordUpdateTest.php`)

| # | Teszteset |
|---|---|
| B01 | Jelszó módosítási oldal megjelenítése |
| B02 | Jelszó módosítható |
| B03 | Helyes jelenlegi jelszó szükséges |

#### Profil módosítás (`ProfileUpdateTest.php`)

| # | Teszteset |
|---|---|
| B04 | Profil oldal megjelenítése |
| B05 | Profil információ módosítható |
| B06 | E-mail változatlan marad ha nem módosított |
| B07 | Felhasználó törölheti fiókját |
| B08 | Helyes jelszó szükséges a törléshez |

#### Kétfaktoros hitelesítés (`TwoFactorAuthenticationTest.php`)

| # | Teszteset |
|---|---|
| B09 | 2FA beállítási oldal renderelése |
| B10 | Engedélyezéskor jelszó-megerősítés szükséges |
| B11 | Letiltott állapotban nem kér megerősítést |
| B12 | Tiltott válasz ha a 2FA funkció kikapcsolt |

---

## 4. API végpontok lefedettségi mátrixa

A következő mátrix mutatja, hogy az egyes API végpontok milyen tesztekkel vannak lefedve:

| Végpont | Auth | CRUD | Válid. | Szűrés | Jogos. | Munkafolyamat | Értesítés |
|---|---|---|---|---|---|---|---|
| `POST /api/register` | ✅ | ✅ | ✅ | - | ✅ | - | - |
| `POST /api/login` | ✅ | ✅ | ✅ | - | - | - | - |
| `POST /api/logout` | ✅ | ✅ | - | - | ✅ | - | - |
| `GET /api/user` | ✅ | ✅ | - | - | ✅ | - | - |
| `GET /api/cars` | ✅ | ✅ | - | ✅ | ✅ | - | - |
| `POST /api/cars` | ✅ | ✅ | ✅ | - | ✅ | - | - |
| `GET /api/cars/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `PUT /api/cars/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `DELETE /api/cars/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `GET /api/tickets` | ✅ | ✅ | - | ✅ | ✅ | - | - |
| `POST /api/tickets` | ✅ | ✅ | ✅ | - | ✅ | - | - |
| `GET /api/tickets/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `PUT /api/tickets/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `DELETE /api/tickets/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `POST /api/tickets/{id}/accept` | - | - | - | - | ✅ | ✅ | ✅ |
| `POST /api/tickets/{id}/start` | - | - | - | - | ✅ | ✅ | ✅ |
| `POST /api/tickets/{id}/complete` | - | - | - | - | ✅ | ✅ | ✅ |
| `POST /api/tickets/{id}/close` | - | - | - | - | ✅ | ✅ | ✅ |
| `GET /api/problems` | ✅ | ✅ | - | ✅ | ✅ | - | - |
| `POST /api/problems` | ✅ | ✅ | ✅ | - | ✅ | - | - |
| `GET /api/problems/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `PUT /api/problems/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `DELETE /api/problems/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `GET /api/users` | ✅ | ✅ | - | ✅ | ✅ | - | - |
| `POST /api/users` | ✅ | ✅ | ✅ | - | ✅ | - | - |
| `GET /api/users/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `PUT /api/users/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `DELETE /api/users/{id}` | ✅ | ✅ | - | - | ✅ | - | - |
| `GET /api/tickets/statistics` | ✅ | ✅ | - | - | ✅ | - | - |
| `GET /api/problems/statistics` | ✅ | ✅ | - | - | ✅ | - | - |

---

## 5. Tesztelési eredmények összefoglalója

### 5.1 Futtatási eredmény

```
Tests:    386 passed (1004 assertions)
Duration: 11.07s
Failures: 0
```

### 5.2 Teszt típus szerinti bontás

| Típus | Fájlok száma | Tesztesetek | Állapot |
|---|---|---|---|
| Unit tesztek | 5 | 22 | ✅ Mind sikeres |
| Feature – API tesztek | 8 | ~200 | ✅ Mind sikeres |
| Feature – Web kontroller | 1 | 48 | ✅ Mind sikeres |
| Feature – Web útvonal | 1 | 46 | ✅ Mind sikeres |
| Feature – Auth (web) | 7 | 26 | ✅ Mind sikeres |
| Feature – Beállítások | 3 | 13 | ✅ Mind sikeres |
| Feature – Egyéb | 1 | 1 | ✅ Mind sikeres |
| **Összesen** | **26** | **386** | **✅ 100% sikeres** |

### 5.3 Tesztkörnyezet

| Paraméter | Érték |
|---|---|
| PHP verzió | 8.3+ |
| Laravel verzió | 12.x |
| Pest verzió | 3.x |
| Adatbázis (teszt) | SQLite in-memory |
| Futtatási idő | ~11 másodperc |
| Konténer | Docker (`app` service) |

---

## 6. Tesztlefedettségi elemzés

### 6.1 Lefedett területek

- ✅ **Hitelesítés (Authentication)**: Regisztráció, bejelentkezés, kijelentkezés, token kezelés
- ✅ **Jogosultságkezelés (Authorization)**: Szerepkör-alapú hozzáférés-vezérlés (RBAC) mind a 3 szerepkörre
- ✅ **CRUD műveletek**: Teljes Create-Read-Update-Delete mind a 4 fő entitásra
- ✅ **Validáció**: Bemeneti adatok validálása, egyediség ellenőrzés
- ✅ **Szűrés és lapozás**: Többszörös szűrők, keresés, lapozási paraméterek
- ✅ **Munkafolyamat**: Teljes jegy életciklus (open → assigned → in_progress → completed → closed)
- ✅ **Értesítések**: E-mail értesítések állapotváltozáskor, deduplikáció
- ✅ **Kapcsolatok**: Modell relációk (hasMany, belongsTo, belongsToMany)
- ✅ **Statisztikák**: Jegy- és problémastatisztikák hozzáférés-vezérléssel
- ✅ **Web felület**: Inertia kontroller válaszok és útvonal hozzáférés

### 6.2 Nem lefedett / jövőbeli tesztelési területek

| Terület | Prioritás | Megjegyzés |
|---|---|---|
| Teljesítménytesztelés (Load testing) | Közepes | Több ezer egyidejű kérés tesztelése |
| E2E (End-to-End) tesztelés | Alacsony | Cypress/Playwright a frontend teszteléshez |
| API rate limiting tesztek | Közepes | Throttle korlátok ellenőrzése |
| Fájlfeltöltés tesztek | Alacsony | Ha a rendszer támogat képfeltöltést |
| Adatbázis-index teljesítmény | Közepes | Nagyméretű adatkészlettel |

---

## 7. OpenAPI specifikáció és tesztek összevetése

A rendszer rendelkezik OpenAPI 3.0.3 specifikációval (`openapi.yaml`), amely tartalmazza az összes API végpont dokumentációját. A tesztkészlet lefedi az OpenAPI-ban dokumentált összes végpontot:

| OpenAPI tag | Végpontok | Tesztlefedettség |
|---|---|---|
| Authentication | 4 | ✅ 100% |
| Users | 8 | ✅ 100% |
| Cars | 6 | ✅ 100% |
| Problems | 6 | ✅ 100% |
| Tickets | 10 | ✅ 100% |
| Health | 1 | ✅ (implicit) |

---

## 8. Tesztfájlok jegyzéke

### Unit tesztek (`tests/Unit/`)

| Fájl | Leírás |
|---|---|
| `ExampleTest.php` | Ticket modell állapot-helperek, castok, kitölthető mezők |
| `CarModelTest.php` | Car modell kitölthető mezők és táblanév |
| `ProblemModelTest.php` | Problem modell kitölthető mezők, castok, táblanév |
| `UserModelTest.php` | User modell kitölthető/rejtett mezők, castok, táblanév |
| `TicketStatusChangedNotificationTest.php` | Értesítés adattárolás, csatorna, array kimenet |

### Feature tesztek (`tests/Feature/`)

| Fájl | Leírás |
|---|---|
| `AuthenticationTest.php` | API regisztráció, bejelentkezés, kijelentkezés, profil |
| `CarApiTest.php` | Autó CRUD API végpontok és jogosultságok |
| `DashboardTest.php` | Dashboard hozzáférés |
| `ExampleTest.php` | Kezdőlap átirányítás teszt |
| `FilteringAndPaginationTest.php` | Szűrés és lapozás tesztek |
| `ProblemApiTest.php` | Probléma CRUD API és statisztikák |
| `RelationshipTest.php` | Modell kapcsolatok tesztelése |
| `RolePermissionTest.php` | Szerepkörök és jogosultságok |
| `StatisticsTest.php` | Statisztika végpontok |
| `TicketApiTest.php` | Jegy CRUD API és munkafolyamat |
| `TicketStatusNotificationTest.php` | Állapotváltozás értesítések |
| `TicketWorkflowTest.php` | Teljes jegy munkafolyamat |
| `UserApiTest.php` | Felhasználó CRUD API |
| `ValidationTest.php` | Bemeneti validáció |
| `WebControllersTest.php` | Web Inertia kontroller tesztek |
| `WebRoutesTest.php` | Web útvonal hozzáférés tesztek |

### Auth tesztek (`tests/Feature/Auth/`)

| Fájl | Leírás |
|---|---|
| `AuthenticationTest.php` | Web bejelentkezés/kijelentkezés |
| `EmailVerificationTest.php` | E-mail ellenőrzés |
| `PasswordConfirmationTest.php` | Jelszó-megerősítés |
| `PasswordResetTest.php` | Jelszó-visszaállítás |
| `RegistrationTest.php` | Web regisztráció |
| `TwoFactorChallengeTest.php` | 2FA kihívás |
| `VerificationNotificationTest.php` | Ellenőrző értesítés |

### Beállítás tesztek (`tests/Feature/Settings/`)

| Fájl | Leírás |
|---|---|
| `PasswordUpdateTest.php` | Jelszó módosítás |
| `ProfileUpdateTest.php` | Profil módosítás és fiók törlés |
| `TwoFactorAuthenticationTest.php` | 2FA beállítások |

---

## 9. Függelék

### 9.1 Teszt futtatási parancsok

```bash
# Összes teszt futtatása
make test

# Csak unit tesztek
docker compose exec app php artisan test --testsuite=Unit

# Csak feature tesztek
docker compose exec app php artisan test --testsuite=Feature

# Egy adott tesztfájl
docker compose exec app php artisan test tests/Feature/TicketApiTest.php

# Szűrés tesztnévre
docker compose exec app php artisan test --filter="users can register"

# Verbose kimenettel
docker compose exec app php artisan test -v
```

### 9.2 Pandoc konverzió

A dokumentum konvertálható PDF-be a Pandoc segítségével:

```bash
pandoc TESTING_DOCS.md -o testing_docs.pdf \
  --pdf-engine=xelatex \
  -V geometry:margin=2.5cm \
  -V mainfont="DejaVu Sans" \
  -V monofont="DejaVu Sans Mono" \
  --highlight-style=tango \
  --toc \
  --toc-depth=3 \
  -V title="OnlyFix Szoftvertesztelési Dokumentáció" \
  -V author="OnlyFix Fejlesztőcsapat" \
  -V date="2026. március 16." \
  -V lang=hu

# HTML konverzió
pandoc TESTING_DOCS.md -o testing_docs.html \
  --standalone \
  --toc \
  --toc-depth=3 \
  --metadata title="OnlyFix Szoftvertesztelési Dokumentáció"

# DOCX konverzió
pandoc TESTING_DOCS.md -o testing_docs.docx \
  --toc \
  --toc-depth=3
```

---

*Dokumentum generálva: 2026. március 16.*
*Verzió: 1.0*
*Tesztkeretrendszer: Pest PHP 3.x, Laravel 12.x*
