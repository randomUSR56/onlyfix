# OnlyFix Project Context

## Project Overview
OnlyFix is an open-source ticket-based management system for vehicle issues. It allows users to track vehicle problems, mechanics to manage repairs, and administrators to oversee the system. The project follows a modern full-stack architecture using Laravel and Vue.js.

- **Primary Domain**: Vehicle repair management and issue tracking.
- **Architecture**: Monolithic Laravel application with Inertia.js for a Single Page Application (SPA) experience.
- **License**: MIT

## Technology Stack
### Backend
- **Framework**: Laravel 12.0 (PHP 8.2+)
- **Authentication**: Laravel Fortify & Sanctum
- **Frontend Adapter**: Inertia.js 2.0
- **Routing**: Laravel Wayfinder
- **Permissions**: Spatie Laravel Permission
- **Database**: MySQL 8.0

### Frontend
- **Framework**: Vue.js 3.5 (TypeScript)
- **Build Tool**: Vite 7.0
- **Styling**: Tailwind CSS 4.1
- **UI Components**: Reka UI, Lucide Icons
- **State/Utilities**: VueUse, Vue-i18n

### Infrastructure & Dev Tools
- **Containerization**: Docker & Docker Compose
- **Orchestration**: Makefile for simplified CLI commands
- **Testing**: Pest PHP
- **Code Quality**: Laravel Pint (PHP), ESLint & Prettier (JS/TS)

## Key Project Structure
- `/onlyfix`: Main Laravel application directory.
    - `/app/Models`: Core entities (`User`, `Car`, `Problem`, `Ticket`).
    - `/app/Http/Controllers`: Logic for Web (Inertia) and API (Sanctum) endpoints.
    - `/resources/js`: Vue 3 application source code.
    - `/routes`: Route definitions (`web.php`, `api.php`, `auth.php`, etc.).
    - `/database`: Migrations, Seeders, and Factories.
- `/docker`: Docker configuration for app, nginx, mysql, etc.
- `/scripts`: Platform-specific setup scripts for hosts and network configuration.

## Development Workflow

### Setup & Initialization
The project uses a `Makefile` to automate the complex setup of Docker containers, local domains, and dependencies.

```bash
# Full initialization (Docker, dependencies, migrations, seeders)
make init
```

### Running the Application
- **Docker-based**: `make start`
- **Local Dev Server**: `cd onlyfix && composer dev` (runs Vite and Artisan serve concurrently)
- **Local URLs**:
    - Application: `http://onlyfix.local`
    - Mailpit: `http://mailpit.onlyfix.local:8025`
    - phpMyAdmin: `http://phpmyadmin.onlyfix.local:8080`

### Common Commands
- **Testing**: `make test` or `cd onlyfix && php artisan test`
- **Database Refresh**: `make fresh`
- **Shell Access**: `make shell` (app) or `make shell-node` (node)
- **Tinker**: `make tinker`

## Coding Standards & Conventions
- **PHP**: Follows Laravel standards, enforced by `laravel/pint`.
- **Frontend**: Functional components in Vue 3, strict TypeScript typing.
- **Styling**: Utility-first CSS with Tailwind CSS 4.
- **Routing**: Uses Laravel Wayfinder for type-safe routing between backend and frontend.
- **Commits**: Clear, concise messages. Do not stage/commit unless explicitly requested.

## Roles & Access
- **Admin**: `admin@example.com` / `password`
- **Mechanic**: `mechanic@example.com` / `password`
- **User**: `test@example.com` / `password`
