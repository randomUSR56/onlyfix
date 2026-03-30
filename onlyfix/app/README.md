# app/ – Laravel alkalmazás logika

## Mappák

| Mappa            | Leírás                                                        |
|------------------|---------------------------------------------------------------|
| `Http/`          | Controllers (web + API), Middleware, Form Requests             |
| `Models/`        | Eloquent modellek: User, Car, Ticket, Problem                 |
| `Services/`      | Üzleti logika: TicketService, CarService, UserService, ProblemService |
| `Notifications/` | Email értesítések (pl. TicketStatusChanged)                    |
| `Policies/`      | Jogosultsági szabályok (TicketPolicy, UserPolicy)              |
| `Providers/`     | Service providerek                                             |

## Architektúra

A controllerek a `Services/` mappába delegálják az üzleti logikát.
A jogosultságkezelés middleware-ben és Policy osztályokban történik.

Szerepkörök (Spatie Permission): `admin`, `mechanic`, `user`.
