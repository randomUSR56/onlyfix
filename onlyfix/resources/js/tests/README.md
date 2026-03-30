# OnlyFix – Frontend tesztek

## Áttekintés

Ez a mappa tartalmazza az OnlyFix frontend réteg egység- és komponenstesztjeit. A tesztek Vitest + Vue Test Utils + happy-dom kombinációval futnak.

## Futtatás

```bash
# Interaktív mód (watch)
npm run test:unit

# Egyszeri futtatás
npm run test:unit:run

# Coverage riporttal
npm run test:coverage
```

## Struktúra

```
resources/js/tests/
├── setup.ts                          # Globális mock-ok
├── unit/
│   └── composables/
│       ├── useAuth.test.ts           # Szerepkör és jogosultság kezelés
│       ├── useTicketHelpers.test.ts  # Státusz/prioritás mapping, fordítás
│       ├── useFormatting.test.ts     # Dátumformázás, HTML entitás dekódolás
│       ├── useInitials.test.ts       # Névkezdobetű számítás
│       └── useAppearance.test.ts     # Téma váltás (light/dark/system)
└── components/
    ├── ConfirmDeleteDialog.test.ts   # Megerősítő törlés dialog
    ├── InputError.test.ts            # Hibaüzenet megjelenítő
    ├── AlertError.test.ts            # Hibák listás megjelenítése
    ├── LanguageSwitcher.test.ts      # Nyelvváltó dropdown
    ├── Breadcrumbs.test.ts           # Kenyérmorzsa navigáció
    └── DeleteUser.test.ts            # Felhasználó törlése
```

## Mock stratégiák

### setup.ts – globális mock-ok

A `setup.ts` fájl az alábbi modulokat mockolja globálisan (minden tesztfájl számára):

- **vue-i18n**: `useI18n()` mock – a `t()` függvény visszaadja a kulcsot vagy a fallback értéket
- **@inertiajs/vue3**: `usePage()`, `Link`, `router`, `useForm`, `Form` mock-ok
- **window.matchMedia**: DOM API mock a témaváltás tesztekhez
- **@/i18n**: az alkalmazás i18n moduljának mockja

### Tesztenkénti mock-ok

A komponens tesztek saját `vi.mock()` hívásokkal stub-olják a UI komponenseket (Dialog, Button, DropdownMenu stb.), megőrizve a slot és event logikát, de eltávolítva a reka-ui/CVA függőségeket.

Az `useAuth.test.ts` a `setMockUser()` helper-t használja a `setup.ts`-ből az Inertia page props szimulálásához.

## Új teszt hozzáadása

1. Hozd létre a fájlt a megfelelő almappában (`unit/composables/` vagy `components/`)
2. A fájlnévnek `.test.ts` végződéssel kell rendelkeznie
3. Ha szükséges, adj hozzá mock-okat a teszt elejére `vi.mock()` hívásokkal
4. A `setup.ts` globális mock-jait minden tesztfájl automatikusan megkapja
