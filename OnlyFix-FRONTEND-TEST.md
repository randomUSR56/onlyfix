# OnlyFix – Frontend Teszt Dokumentáció

## Áttekintés

Az OnlyFix frontend réteg egység- és komponenstesztjei a Vue 3 composable-k üzleti logikáját és a UI komponensek helyes működését ellenőrzik. A tesztek Vitest keretrendszerrel, happy-dom környezetben futnak. Utolsó futtatás: 2026-03-30, 116/116 teszt sikeres.

## Technológiai stack

- Vitest 4.1.2
- @vue/test-utils 2.4.6
- happy-dom 20.8.9
- @vitest/coverage-v8 4.1.2

## Tesztstruktúra

| Fájl | Típus | Tesztesetek | Eredmény |
|------|-------|:-----------:|:--------:|
| useAuth.test.ts | Egység | 26 | ✅ |
| useTicketHelpers.test.ts | Egység | 30 | ✅ |
| useFormatting.test.ts | Egység | 10 | ✅ |
| useInitials.test.ts | Egység | 9 | ✅ |
| useAppearance.test.ts | Egység | 7 | ✅ |
| ConfirmDeleteDialog.test.ts | Komponens | 5 | ✅ |
| InputError.test.ts | Komponens | 5 | ✅ |
| AlertError.test.ts | Komponens | 6 | ✅ |
| LanguageSwitcher.test.ts | Komponens | 5 | ✅ |
| Breadcrumbs.test.ts | Komponens | 6 | ✅ |
| DeleteUser.test.ts | Komponens | 7 | ✅ |
| **Összesen** | | **116** | **✅** |

## Egység teszt – useTicketHelpers

**Fájl:** `resources/js/tests/unit/composables/useTicketHelpers.test.ts`
**Lefedett funkció:** Ticket státusz/prioritás badge variánsok, ikonok, háttérszínek, szerepkör badge-ek és probléma fordítás mapping-ek.

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

**Mock stratégia:** A `vue-i18n` useI18n `t()` függvénye kulcs-fallback módban mockolt; a composable tiszta függvényeket exportál, így egyéb mock nem szükséges.

## Komponens teszt – DeleteUser

**Fájl:** `resources/js/tests/components/DeleteUser.test.ts`
**Lefedett funkció:** Felhasználó törlése panel: dialog megjelenítés, gomb variánsok, jelszó mező és megerősítő elemek renderelése.

| Teszteset | Eredmény |
|-----------|:--------:|
| Megjelenik a törlés gomb | ✅ |
| Tartalmazza a figyelmeztetés szöveget | ✅ |
| Tartalmazza a heading-et | ✅ |
| A törlés gomb destructive variánsú | ✅ |
| Tartalmazza a megerősítő dialog elemet | ✅ |
| Tartalmazza a jelszó beviteli mezőt | ✅ |
| Tartalmazza a mégse gombot | ✅ |

**Mock stratégia:** Dialog/Button/Input UI stub-ok, HeadingSmall és InputError stub-ok, Inertia useForm mock, `$t` globális mock a setup.ts-ből.

## Coverage összesítő

| Terület | Statements | Branches | Functions | Lines |
|---------|:----------:|:--------:|:---------:|:-----:|
| Összesítés | 32.62% | 30.62% | 36.56% | 29.76% |
| composables/ | 57.14% | 66.19% | 67.85% | 52.25% |
| components/ | 21.25% | 13.17% | 26.47% | 19.86% |

## Futtatási eredmény

- **Összes teszt:** 116
- **Sikeres:** 116
- **Sikertelen:** 0
- **Futási idő:** 1.55s

## Ismert korlátok

- **useTwoFactorAuth** nem tesztelt: aszinkron fetch hívások és route generátorok komplex mock igénye miatt.
- **useAppearance** részlegesen tesztelt: az `onMounted` lifecycle hook a happy-dom korlátai miatt nem lefedett.
- **AppSidebar és nav komponensek** nem teszteltek: szerep-alapú navigáció Inertia route + sidebar state kombinált mock-ot igényelne.
