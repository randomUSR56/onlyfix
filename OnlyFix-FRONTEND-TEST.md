# OnlyFix – Frontend egység és komponens tesztek dokumentáció

## 1. Áttekintés

### Cél
Az OnlyFix frontend réteg tesztelése egység- és komponensszinten annak biztosítására, hogy a Vue 3 composable-k és komponensek az elvárásoknak megfelelően működnek.

### Technológiai stack
| Eszköz | Verzió | Szerep |
|--------|--------|--------|
| Vitest | ^4.1.2 | Tesztfuttató framework |
| @vue/test-utils | ^2.4.6 | Vue komponens tesztelés |
| happy-dom | ^20.8.9 | DOM szimuláció |
| @vitest/coverage-v8 | ^4.1.2 | Lefedettség mérés |

### Tesztelt területek
- **5 composable** – üzleti logika, formázás, szerepkörkezelés, megjelenés
- **6 komponens** – prop-alapú renderelés, eseménykibocsátás, feltételes megjelenítés

---

## 2. Tesztstruktúra

```
resources/js/tests/
├── setup.ts                              # Globális mock-ok (i18n, Inertia, matchMedia)
├── unit/
│   └── composables/
│       ├── useAuth.test.ts               # Szerepkör és jogosultság kezelés
│       ├── useTicketHelpers.test.ts       # Státusz/prioritás mapping-ek
│       ├── useFormatting.test.ts          # Dátumformázás, HTML entitás dekódolás
│       ├── useInitials.test.ts            # Névkezdobetűk számítása
│       └── useAppearance.test.ts          # Téma váltás (light/dark/system)
└── components/
    ├── ConfirmDeleteDialog.test.ts        # Megerősítő törlés dialog
    ├── InputError.test.ts                 # Hibaüzenet megjelenítő
    ├── AlertError.test.ts                 # Hibák listás megjelenítése
    ├── LanguageSwitcher.test.ts           # Nyelvváltó dropdown
    ├── Breadcrumbs.test.ts                # Kenyérmorzsa navigáció
    └── DeleteUser.test.ts                 # Felhasználó törlése
```

### Tesztfájlok összefoglalója

| Fájl | Típus | Tesztesetek száma | Lefedett funkció |
|------|-------|:-----------------:|-----------------|
| useAuth.test.ts | Egység | 26 | Szerepkör, jogosultság, üzleti szabályok |
| useTicketHelpers.test.ts | Egység | 30 | Státusz/prioritás variánsok, fordítás |
| useFormatting.test.ts | Egység | 10 | Dátumformázás, pagination label dekódolás |
| useInitials.test.ts | Egység | 9 | Névkezdobetű-számítás |
| useAppearance.test.ts | Egység | 7 | Témaváltás, localStorage |
| ConfirmDeleteDialog.test.ts | Komponens | 5 | Open/close, események, prop render |
| InputError.test.ts | Komponens | 5 | v-show logika, hibaüzenet megjelenítés |
| AlertError.test.ts | Komponens | 6 | Deduplikáció, feltételes render, variáns |
| LanguageSwitcher.test.ts | Komponens | 5 | Nyelvváltás, locale highlight |
| Breadcrumbs.test.ts | Komponens | 6 | Link/page elválasztás, üres tömb |
| DeleteUser.test.ts | Komponens | 7 | Dialog megjelenés, gomb variánsok |
| **Összesen** | | **116** | |

---

## 3. Egység tesztek – composable-k

### useAuth

Felelősség: Felhasználói szerepkörök és jogosultságok kezelése Inertia page props alapján.

| Teszteset | Eredmény |
|-----------|:--------:|
| getRoleName string típusú szerepkört stringként adja vissza | ✅ |
| getRoleName objektum típusú szerepkörből a name mezőt adja vissza | ✅ |
| isAdmin true ha a szerepkör admin | ✅ |
| isAdmin false ha a szerepkör user | ✅ |
| isAdmin false ha a szerepkör mechanic | ✅ |
| isMechanic true ha a szerepkör mechanic | ✅ |
| isMechanic false ha a szerepkör user | ✅ |
| isUser true ha a szerepkör user | ✅ |
| isAuthenticated true ha a felhasználó létezik | ✅ |
| canViewAllTickets true mechanic esetén | ✅ |
| canViewAllTickets true admin esetén | ✅ |
| canViewAllTickets false user esetén | ✅ |
| canManageUsers true admin esetén | ✅ |
| canManageUsers false mechanic esetén | ✅ |
| canAcceptTickets true mechanic esetén | ✅ |
| canAcceptTickets false admin esetén | ✅ |
| hasPermission true ha van egyező jogosultság | ✅ |
| hasPermission false ha nincs egyező jogosultság | ✅ |
| hasAnyPermission true ha legalább egy egyezik | ✅ |
| hasAllPermissions true ha minden megvan | ✅ |
| hasAllPermissions false ha nem minden van meg | ✅ |
| hasRole működik string szerepkörökkel | ✅ |
| hasAnyRole true ha legalább egy egyezik | ✅ |
| hasAllRoles true ha minden megvan | ✅ |
| üres roles tömb esetén isAdmin false | ✅ |
| undefined roles esetén isAdmin false | ✅ |

### useTicketHelpers

Felelősség: Ticket státusz/prioritás megjelenítés, probléma fordítás.

| Teszteset | Eredmény |
|-----------|:--------:|
| getStatusBadgeVariant – open → destructive | ✅ |
| getStatusBadgeVariant – assigned → secondary | ✅ |
| getStatusBadgeVariant – in_progress → default | ✅ |
| getStatusBadgeVariant – completed → outline | ✅ |
| getStatusBadgeVariant – closed → outline | ✅ |
| getStatusBadgeVariant – ismeretlen → secondary fallback | ✅ |
| getPriorityBadgeClass – urgent → piros | ✅ |
| getPriorityBadgeClass – high → narancs | ✅ |
| getPriorityBadgeClass – medium → sárga | ✅ |
| getPriorityBadgeClass – low → zöld | ✅ |
| getPriorityBadgeClass – ismeretlen → szürke fallback | ✅ |
| getStatusIcon – minden ismert státuszhoz van ikon | ✅ |
| getStatusIcon – ismeretlen → Clock fallback | ✅ |
| translateProblem – fallback a name-re ha nincs fordítás | ✅ |
| translateProblem – üres description fallback | ✅ |
| getRoleBadgeVariant – admin → destructive | ✅ |
| getRoleBadgeVariant – mechanic → default | ✅ |
| getRoleBadgeVariant – user → secondary | ✅ |
| getRoleBadgeVariant – ismeretlen → outline | ✅ |
| getStatusBgColorClass – open → narancs | ✅ |
| getStatusBgColorClass – assigned → kék | ✅ |
| getStatusBgColorClass – in_progress → kék | ✅ |
| getStatusBgColorClass – completed → zöld | ✅ |
| getStatusBgColorClass – closed → zöld | ✅ |
| getStatusBgColorClass – ismeretlen → muted | ✅ |
| getStatusIconColorClass – open → narancs | ✅ |
| getStatusIconColorClass – assigned → kék | ✅ |
| getStatusIconColorClass – in_progress → kék | ✅ |
| getStatusIconColorClass – completed → zöld | ✅ |
| getStatusIconColorClass – ismeretlen → muted-foreground | ✅ |

### useFormatting

Felelősség: Dátumformázás és pagination label HTML entitás dekódolás.

| Teszteset | Eredmény |
|-----------|:--------:|
| formatDate – ISO dátumstringet formázott szöveggé alakít | ✅ |
| formatDate – másik dátumot is helyesen formáz | ✅ |
| formatLongDate – hosszú formátumot ad vissza | ✅ |
| formatLongDate – időt is tartalmaz | ✅ |
| formatSimpleDate – egyszerű formátum | ✅ |
| decodePaginationLabel – &laquo; → « | ✅ |
| decodePaginationLabel – &raquo; → » | ✅ |
| decodePaginationLabel – &amp; → & | ✅ |
| decodePaginationLabel – kombinált entitások | ✅ |
| decodePaginationLabel – entitás nélkül változatlan | ✅ |

### useInitials

Felelősség: Felhasználói avatár kezdőbetűk számítása.

| Teszteset | Eredmény |
|-----------|:--------:|
| 'John Doe' → 'JD' | ✅ |
| 'Single' → 'S' | ✅ |
| 'John Middle Doe' → 'JD' | ✅ |
| üres string → '' | ✅ |
| undefined → '' | ✅ |
| kisbetűs → nagybetűs | ✅ |
| felesleges szóközök kezelése | ✅ |
| magyar ékezetes nevek ('Ádám Éva' → 'ÁÉ') | ✅ |
| useInitials composable getInitials-t ad vissza | ✅ |

### useAppearance

Felelősség: Témaváltás (light/dark/system), localStorage és matchMedia integráció.

| Teszteset | Eredmény |
|-----------|:--------:|
| updateTheme dark → dark osztály hozzáadva | ✅ |
| updateTheme light → dark osztály eltávolítva | ✅ |
| updateTheme system → matchMedia alapján (light) | ✅ |
| updateTheme system → matchMedia dark mód | ✅ |
| localStorage-ba mentés és visszaolvasás (dark) | ✅ |
| localStorage-ba mentés (light) | ✅ |
| localStorage-ba mentés (system) | ✅ |

---

## 4. Komponens tesztek

### ConfirmDeleteDialog

Mock stratégia: Dialog, DialogContent, Button UI stub-ok; `$t` mock.

| Teszteset | Eredmény |
|-----------|:--------:|
| Nem renderel semmit ha open prop false | ✅ |
| Megjelenik ha open prop true | ✅ |
| A title és description prop megjelenik | ✅ |
| confirm eseményt bocsát ki a törlés gomb kattintásakor | ✅ |
| update:open false eseményt bocsát ki a mégse gomb kattintásakor | ✅ |

### InputError

Mock stratégia: Nincs szükség mock-ra, tiszta komponens.

| Teszteset | Eredmény |
|-----------|:--------:|
| Nem jeleníti meg ha message prop üres string (display: none) | ✅ |
| Nem jeleníti meg ha message prop undefined (display: none) | ✅ |
| Megjeleníti a hibaüzenetet ha message meg van adva | ✅ |
| A hibaüzenet szövege egyezik a message prop-pal | ✅ |
| Piros szövegszínt alkalmaz (text-red-600) | ✅ |

### AlertError

Mock stratégia: Alert UI stub; vue-i18n useI18n mock; lucide-vue-next stub.

| Teszteset | Eredmény |
|-----------|:--------:|
| Nem renderel semmit ha errors tömb üres | ✅ |
| Megjeleníti a hibaüzeneteket | ✅ |
| Deduplikálja az azonos hibaüzeneteket | ✅ |
| Egyedi title prop-ot jelenít meg | ✅ |
| Alapértelmezett címet használ ha title nincs megadva | ✅ |
| destructive variánssal renderel | ✅ |

### LanguageSwitcher

Mock stratégia: @/i18n modul (vi.hoisted locale ref); DropdownMenu UI stub-ok.

| Teszteset | Eredmény |
|-----------|:--------:|
| Rendereli a nyelvi opciókat (2 db) | ✅ |
| Megjeleníti a nativeName-eket (English, Magyar) | ✅ |
| Kattintásra hívja a setLocale-t 'en' értékkel | ✅ |
| Kattintásra hívja a setLocale-t 'hu' értékkel | ✅ |
| Az aktív locale kiemelt stílusú (bg-accent) | ✅ |

### Breadcrumbs

Mock stratégia: Breadcrumb UI stub-ok (BreadcrumbLink, BreadcrumbPage stb.); Inertia Link mock.

| Teszteset | Eredmény |
|-----------|:--------:|
| Rendereli az összes breadcrumb elemet | ✅ |
| Az utolsó elem BreadcrumbPage-ként jelenik meg (nem link) | ✅ |
| A közbenső elemek linkként jelennek meg | ✅ |
| Elválasztókat renderel a közbülső elemek között | ✅ |
| Egy elemű tömb esetén csak BreadcrumbPage-t renderel | ✅ |
| Üres tömb esetén nem renderel elemeket | ✅ |

### DeleteUser

Mock stratégia: ProfileController stub; Dialog/Button/Input UI stub-ok; HeadingSmall/InputError stub-ok; Inertia Form mock; `$t` mock.

| Teszteset | Eredmény |
|-----------|:--------:|
| Megjelenik a törlés gomb | ✅ |
| Tartalmazza a figyelmeztetés szöveget | ✅ |
| Tartalmazza a heading-et | ✅ |
| A törlés gomb destructive variánsú | ✅ |
| Tartalmazza a megerősítő dialog elemet | ✅ |
| Tartalmazza a jelszó beviteli mezőt | ✅ |
| Tartalmazza a mégse gombot | ✅ |

---

## 5. Coverage riport

| Fájl | Statements | Branches | Functions | Lines |
|------|:----------:|:--------:|:---------:|:-----:|
| **Összesítés** | **32.62%** | **30.62%** | **36.56%** | **29.76%** |
| **composables/** | **57.14%** | **66.19%** | **67.85%** | **52.25%** |
| useAuth.ts | 100% | 83.33% | 100% | 100% |
| useFormatting.ts | 100% | 100% | 100% | 100% |
| useInitials.ts | 88.88% | 83.33% | 100% | 100% |
| useTicketHelpers.ts | 100% | 94.11% | 100% | 100% |
| useAppearance.ts | 28.94% | 23.8% | 11.11% | 28.94% |
| useTwoFactorAuth.ts | 0% | 0% | 0% | 0% |
| **components/** | **21.25%** | **13.17%** | **26.47%** | **19.86%** |
| AlertError.vue | 100% | 100% | 100% | 100% |
| Breadcrumbs.vue | 100% | 83.33% | 100% | 100% |
| ConfirmDeleteDialog.vue | 91.66% | 100% | 90.9% | 100% |
| DeleteUser.vue | 87.5% | 100% | 86.66% | 90.47% |
| InputError.vue | 100% | 100% | 100% | 100% |
| LanguageSwitcher.vue | 100% | 50% | 100% | 100% |

> A tesztelt fájlok egyedileg kiváló lefedettséget mutatnak (80–100%). Az összesítés alacsonyabb, mert a nem tesztelt komponensek is benne vannak.

---

## 6. Futtatási eredmények

| Metrika | Érték |
|---------|-------|
| Összes teszt | 116 db |
| Sikeres | 116 db |
| Sikertelen | 0 db |
| Tesztfájlok | 11 db (mind sikeres) |
| Futási idő | ~1.6s |

### Futtatási parancsok

```bash
# Egység tesztek interaktív módban
npm run test:unit

# Egész tesztkészlet egyszeri futtatás
npm run test:unit:run

# Coverage riporttal
npm run test:coverage
```

---

## 7. Mock stratégiák

### vue-i18n mock

A `setup.ts`-ben globálisan mockoljuk a `vue-i18n` modult:

```ts
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string, fallback?: string) => fallback ?? key,
        locale: ref('hu'),
    }),
}));
```

A `$t` függvény visszaadja a kulcsot vagy a fallback értéket, így a tesztek nem függenek a fordítási fájloktól.

### Inertia usePage/useForm mock

```ts
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: mockPageProps.value }),
    Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
    router: { visit: vi.fn(), post: vi.fn(), delete: vi.fn() },
    useForm: vi.fn((data) => ({ ...data, processing: false, errors: {} })),
    Form: { template: '<form><slot v-bind="{ errors: {}, processing: false }" /></form>' },
}));
```

A `setMockUser()` és `setMockPageProps()` helper függvények exportálva vannak a `setup.ts`-ből, és tesztenként állítják a szimulált felhasználói adatokat.

### Szerepkör szimulálás

```ts
setMockUser({
    id: 1,
    name: 'Admin',
    email: 'admin@test.com',
    roles: [{ name: 'admin' }],
    permissions: ['edit-tickets'],
});
```

Támogatja mind a string (`'admin'`), mind az objektum (`{ name: 'admin' }`) formátumú szerepköröket.

### UI komponens stub-ok

A komplex UI komponensek (Dialog, Button, DropdownMenu stb.) egyszerű stub-okkal vannak helyettesítve, melyek megtartják a slot és event logikát, de nem függenek a reka-ui vagy class-variance-authority könyvtáraktól.

### LanguageSwitcher – vi.hoisted

A `vi.hoisted()` segítségével oldjuk meg a mock hoisting problémát, így a módosítható `ref()` elérhető a `vi.mock()` factory-ban.

---

## 8. Ismert korlátok és jövőbeli fejlesztések

### Nem tesztelt területek

| Modul | Indoklás |
|-------|----------|
| useTwoFactorAuth | Aszinkron fetch hívások, route URL generátorok – komplex mock szükséges |
| useAppearance (composable rész) | `onMounted` lifecycle hook Happy-DOM korlátai |
| TwoFactorSetupModal | Háromállapotú modal, PinInput, clipboard – integrációs teszt lenne célszerű |
| TwoFactorRecoveryCodes | Aszinkron fetch, scroll, animáció – integrációs teszt |
| AppSidebar | Szerepkör-alapú navigáció – useAuth + route mock kombináció |
| NavUser, NavMain, NavFooter | Inertia page URL + sidebar state függőség |

### Javasolt következő tesztek

1. **useTwoFactorAuth** – fetch mock-kal, a QR kód és recovery kódok lekérdezés folyamat tesztelése
2. **AppSidebar** – szerep-alapú navigáció tesztelése (admin/mechanic/user menüelemek)
3. **E2E tesztek** – Cypress vagy Playwright a teljes felhasználói folyamatok tesztelésére (bejelentkezés, ticket létrehozás, 2FA beállítás)
4. **Snapshot tesztek** – UI komponensek regressziós tesztelésére
5. **i18n integráció** – valódi fordítási fájlokkal való tesztelés a kulcs-fallback helyett
