# database/ – Adatbázis

## Mappák

| Mappa          | Leírás                                                    |
|----------------|-----------------------------------------------------------|
| `migrations/`  | Adatbázis séma (users, cars, problems, tickets, ticket_problems, permission tables) |
| `seeders/`     | Tesztadatok: DatabaseSeeder, RolePermissionSeeder, ProblemSeeder, CarSeeder, TicketSeeder |
| `factories/`   | Factory osztályok teszteléshez (UserFactory, CarFactory, TicketFactory, ProblemFactory) |

## Seed adatok

A `php artisan migrate:fresh --seed` parancs létrehozza:
- 15 felhasználó (1 admin, 3 szerelő, 11 user)
- 29 autó
- 56 hibatípus 8 kategóriában
- 35 hibajegy különböző státuszokkal
