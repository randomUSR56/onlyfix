# resources/js/ – Vue 3 frontend

## Mappák

| Mappa          | Leírás                                                    |
|----------------|-----------------------------------------------------------|
| `pages/`       | Inertia oldalak (Cars/, Tickets/, Users/, Help/, auth/)   |
| `components/`  | Újrafelhasználható Vue komponensek                        |
| `components/ui/` | shadcn/ui alapú UI primitívek                           |
| `composables/` | Vue composable-k (useAuth, useTicketHelpers, stb.)        |
| `layouts/`     | Oldal layoutok (AppLayout, AuthLayout)                    |
| `locales/`     | i18n fordítások (hu.json, en.json)                        |
| `help/`        | Súgó markdown fájlok szerepkörönként és nyelvenként       |
| `types/`       | TypeScript típusdefiníciók                                |
| `routes/`      | Wayfinder által generált route helper-ek                  |
| `actions/`     | Wayfinder által generált action helper-ek                 |

## Belépési pontok

- `app.ts` – Inertia alkalmazás inicializálása
- `ssr.ts` – Server-side rendering belépési pont
