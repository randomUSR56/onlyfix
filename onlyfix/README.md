# OnlyFix - Car Service Management System

A comprehensive full-stack car service management application built with Laravel 12 and Vue 3. Features role-based access control, complete ticket workflow management, real-time statistics, and an interactive API with mock data for testing.

## ✨ Highlights

- **🎯 Full-Stack Application**: Laravel 12 backend + Vue 3 frontend with Inertia.js
- **🔐 Advanced Authentication**: Laravel Sanctum with role-based permissions
- **📊 Mock Data Ready**: Comprehensive seeders with 56 car problems, 35+ tickets, and realistic test data
- **📖 API Documentation**: OpenAPI/Swagger specification with interactive testing
- **🧪 Fully Tested**: Complete test suite with factories for easy data generation
- **🎨 Modern UI**: Tailwind CSS 4, Reka UI components, and responsive design

## 🚀 Features

### Core Functionality

- **User Management** with three-tier role system (User, Mechanic, Admin)
- **Car Management** - Multi-car ownership with detailed vehicle information
- **Problem Catalog** - 56 pre-configured car problems across 8 categories
- **Service Tickets** - Complete workflow with status tracking and problem associations
- **RESTful API** - 40+ endpoints with comprehensive validation
- **Statistics Dashboard** - Real-time metrics for tickets and problems
- **Authentication** - Secure token-based auth with Laravel Sanctum

### Roles & Permissions

- **👤 User**: Manage own cars and tickets, view service history
- **🔧 Mechanic**: View all tickets, accept work, update statuses, access statistics
- **👨‍💼 Admin**: Full system access, user management, system configuration

### Ticket Workflow

```
Open → Assigned → In Progress → Completed → Closed
```

Each status transition is tracked with timestamps and supports multiple problems per ticket.

## 📋 Requirements

- **PHP** 8.2+
- **Composer** 2.x
- **MySQL** 8.0+ or PostgreSQL 13+
- **Node.js** 18+ and npm
- **Git** for version control

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd onlyfix
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=onlyfix
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database Setup

Run migrations and seed the database with roles, permissions, and mock data:

```bash
# Run migrations and all seeders (includes 15 users, 29 cars, 56 problems, 35 tickets)
php artisan migrate:fresh --seed
```

**Or run migrations only:**

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

**Seeded test accounts:**

- **Admin:** admin@example.com / password
- **Mechanic:** mechanic@example.com / password
- **User:** test@example.com / password artisan db:seed --class=RolePermissionSeeder

````

### 5. Set Up Swagger UI
### 6. Start the Development Server

```bash
# Start Laravel backend
php artisan serve

# In another terminal, start Vite for frontend
npm run dev
````

Your application is now available at:

- **Backend API:** `http://localhost:8000/api`
- **Frontend:** `http://localhost:8000`
- **API Docs:** `http://localhost:8000/swagger.html`

## 📊 Mock Data Overview

The application comes with comprehensive mock data for immediate testing:

| Resource          | Count | Description                                                                 |
| ----------------- | ----- | --------------------------------------------------------------------------- |
| **Users**         | 15    | 1 admin, 3 mechanics, 11 regular users                                      |
| **Cars**          | 29    | Realistic makes/models (Toyota, BMW, Honda, etc.)                           |
| **Problems**      | 56    | 54 active problems across 8 categories                                      |
| **Tickets**       | 35    | Various statuses: 5 open, 4 assigned, 6 in-progress, 8 completed, 12 closed |
| **Relationships** | 63    | Ticket-problem associations with notes                                      |

**Problem Categories:** Engine, Transmission, Electrical, Brakes, Suspension, Steering, Body, Other

See `SEEDERS_AND_FACTORIES.md` for detailed documentation.
'email' => 'user@test.com',
'password' => bcrypt('password')
]);

// Create some problems
Problem::factory()->count(10)->create();

```

## 📚 API Documentation

### Base URL

```

http://localhost:8000/api

```

### Interactive Documentation

- **Swagger UI:** `http://localhost:8000/swagger.html` - Interactive API testing interface
- **OpenAPI Spec:** `openapi.yaml` - Complete API specification
- **Postman Collection:** `postman_collection.json` - Import-ready collection

### Authentication

All endpoints (except `/api/health`, `/api/register`, `/api/login`) require a Bearer token:

```

Authorization: Bearer YOUR_TOKEN_HERE

```

Get your token by logging in via `/api/login` - it will be returned in the response.

### Authentication

All endpoints (except `/api/health`, `/api/register`, `/api/login`) require a Bearer token:

```

Authorization: Bearer YOUR_TOKEN_HERE

````

### Quick Start

1. **Register a User**

```bash
POST /api/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
````

2. **Login**

```bash
POST /api/login
{
  "email": "john@example.com",
  "password": "password123"
}
```

Save the `token` from the response.

3. **Create a Car**

````bash
POST /api/cars
Authorization: Bearer YOUR_TOKEN
{
  "make": "Toyota",
  "model": "Camry",
  "year": 2020,
  "license_plate": "ABC-1234"
### API Endpoints Summary (40+ endpoints)

#### 🔐 Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login and receive token
- `POST /api/logout` - Logout (revoke token)
- `GET /api/user` - Get authenticated user info

#### 👥 Users (8 endpoints)
- `GET /api/users/me` - Current user profile
- `GET /api/users` - List all users (Admin)
- `POST /api/users` - Create user (Admin)
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user (Admin)
- `GET /api/users/mechanics` - List available mechanics
- `GET /api/users/{id}/tickets` - User's tickets
- `GET /api/users/{id}/cars` - User's cars

#### 🚗 Cars (6 endpoints)
- `GET /api/cars` - List cars (filtered by ownership)
- `POST /api/cars` - Register new car
- `GET /api/cars/{id}` - Car details
- `PUT /api/cars/{id}` - Update car info
- `DELETE /api/cars/{id}` - Remove car
- `GET /api/cars/{id}/tickets` - Car's service history

#### 🔧 Problems (6 endpoints)
- `GET /api/problems` - List problems (filter by category, active status)
- `POST /api/problems` - Create problem (Mechanic/Admin)
- `GET /api/problems/{id}` - Problem details
- `PUT /api/problems/{id}` - Update problem (Mechanic/Admin)
- `DELETE /api/problems/{id}` - Deactivate problem (Admin)
- `GET /api/problems/statistics` - Problem frequency stats (Mechanic/Admin)

#### 🎫 Tickets (10 endpoints)
- `GET /api/tickets` - List tickets (filter by status, user, car)
- `POST /api/tickets` - Create service ticket
- `GET /api/tickets/{id}` - Ticket details with problems
- `PUT /api/tickets/{id}` - Update ticket
- `DELETE /api/tickets/{id}` - Delete ticket
- `POST /api/tickets/{id}/accept` - Mechanic accepts ticket
- `POST /api/tickets/{id}/start` - Start working on ticket
- `POST /api/tickets/{id}/complete` - Mark work complete
- `POST /api/tickets/{id}/close` - Close ticket (User/Admin)
- `GET /api/tickets/statistics` - Ticket analytics (Mechanic/Admin)

#### 💊 Health
- `GET /api/health` - API health check (no auth)

**Query Parameters Supported:**
- Filtering: `?status=open`, `?category=engine`, `?user_id=1`
- Pagination: `?page=1&per_page=15`
- Sorting: `?sort_by=created_at&sort_order=desc`
- Relationships: `?include=user,car,problems`
#### Tickets

- `GET /api/tickets` - List tickets
- `POST /api/tickets` - Create ticket
- `GET /api/tickets/{id}` - Get ticket
- `PUT /api/tickets/{id}` - Update ticket
- `DELETE /api/tickets/{id}` - Delete ticket
- `POST /api/tickets/{id}/accept` - Accept ticket (Mechanic)
- `POST /api/tickets/{id}/start` - Start work (Mechanic)
- `POST /api/tickets/{id}/complete` - Complete ticket (Mechanic)
- `POST /api/tickets/{id}/close` - Close ticket (User/Admin)
- `GET /api/tickets/statistics` - Ticket statistics (Mechanic/Admin)

#### Health

## 🧪 Testing

### Run All Tests

```bash
php artisan test
````

### Run Specific Test Suite

```bash
php artisan test --filter=CarApiTest
php artisan test --filter=TicketApiTest
php artisan test --filter=UserApiTest
php artisan test --filter=ProblemApiTest
php artisan test --filter=RolePermissionTest
```

### With Coverage

```bash
php artisan test --coverage
```

### Test Features

- ✅ **Authentication tests** - Registration, login, logout, token validation
- ✅ **CRUD operations** - All resources fully tested
- ✅ **Authorization tests** - Role and permission enforcement
- ✅ **Relationship tests** - Model associations and cascading
- ✅ **Validation tests** - Request validation rules
- ✅ **Workflow tests** - Ticket status transitions

### Factory Usage in Tests

```php
// Create test data easily
$user = User::factory()->asUser()->create();
$mechanic = User::factory()->asMechanic()->create();
$car = Car::factory()->forUser($user)->create();
$ticket = Ticket::factory()->forCar($car)->open()->create();
$problems = Problem::factory()->count(5)->create();
```

Tests use an in-memory SQLite database by default. Configure in `phpunit.xml`.

## 📖 Using Postman

### Import Collection

1. Open Postman
2. Click "Import"
3. Select `postman_collection.json` from project root
4. Collection "OnlyFix API" will be imported

### Setup Environment

1. Create new environment "OnlyFix Local"
2. Set variables:
    - `base_url`: `http://localhost:8000`
    - `access_token`: (will be set automatically on login)

### Quick Test

1. Send request: Authentication → Register
2. Send request: Authentication → Login
3. Token is auto-saved to environment

## 🏗️ Project Structure

````
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/              # RESTful API controllers (5 controllers)
│   │   ├── Auth/             # Authentication controllers
│   │   └── Settings/         # Application settings
│   ├── Middleware/           # Custom middleware
│   └── Requests/             # Form request validation
├── Models/                   # Eloquent models (4 core models)
│   ├── Car.php              # Vehicle management
│   ├── Problem.php          # Problem catalog
│   ├── Ticket.php           # Service tickets
│   └── User.php             # User authentication & roles
└── Policies/                 # Authorization policies
    ├── TicketPolicy.php
    └── UserPolicy.php

database/
├── factories/                # Model factories for testing
│   ├── CarFactory.php       # 50+ realistic car combinations
│   ├── ProblemFactory.php   # Category-based problems
│   ├── TicketFactory.php    # Status-based tickets
│   └── UserFactory.php      # Role-based users
├── migrations/               # Database schema (10 migrations)
│   ├── create_users_table
│   ├── create_cars_table
│   ├── create_problems_table
│   ├── create_tickets_table
│   ├── create_ticket_problems_table
│   └── create_permission_tables
└── seeders/                  # Data seeders
    ├── DatabaseSeeder.php   # Main orchestrator
    ├── RolePermissionSeeder.php
    ├── ProblemSeeder.php    # 56 car problems
    ├── CarSeeder.php        # Realistic vehicles
    └── TicketSeeder.php     # Service tickets

resources/
├── css/                      # Tailwind CSS styles
├── js/                       # Vue 3 components
│   ├── components/          # Reusable UI components
│   ├── layouts/             # Page layouts
## 🔐 Security Features

- **Authentication**: Laravel Sanctum SPA & API token authentication
- **Authorization**: Role-based access control (RBAC) with Spatie Laravel Permission
- **Password Hashing**: Bcrypt with configurable rounds
- **Validation**: Comprehensive request validation on all endpoints
- **Policies**: Resource-level authorization (UserPolicy, TicketPolicy)
- **CSRF Protection**: Enabled on web routes
- **Rate Limiting**: API throttling (60 requests/minute)
## 🎯 Database Schema

### Users
```sql
- id, name, email, password
- email_verified_at, remember_token
- two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at
- timestamps
- Relationships: cars (hasMany), tickets (hasMany), assignedTickets (hasMany)
- Roles: user, mechanic, admin (via Spatie Permission)
````

### Cars

```sql
- id, user_id (foreign)
- make, model, year
- license_plate (unique), vin (unique, nullable)
- color
- timestamps
- Relationships: user (belongsTo), tickets (hasMany)
```

### Problems

```sql
- id, name (unique)
- category (enum: engine, transmission, electrical, brakes, suspension, steering, body, other)
- description, is_active (boolean)
- timestamps
- Relationships: tickets (belongsToMany via ticket_problems)
```

### Tickets

```sql
- id, user_id (foreign), mechanic_id (foreign, nullable), car_id (foreign)
- status (enum: open, assigned, in_progress, completed, closed)
- priority (enum: low, medium, high, urgent)
- description (text)
- accepted_at, completed_at (timestamps)
- created_at, updated_at
- Relationships: user (belongsTo), mechanic (belongsTo), car (belongsTo), problems (belongsToMany)
```

### Ticket_Problems (Pivot)

```sql
- id, ticket_id (foreign), problem_id (foreign)
- notes (text, nullable)
- timestamps
- Unique constraint on [ticket_id, problem_id]
```

### Roles & Permissions (Spatie)

```sql
- roles: id, name, guard_name
- permissions: id, name, guard_name
- model_has_roles: model_id, role_id, model_type
- model_has_permissions: model_id, permission_id, model_type
- role_has_permissions: role_id, permission_id
```

    ├── CarApiTest.php
    ├── ProblemApiTest.php
    ├── RolePermissionTest.php
    ├── TicketApiTest.php
    └── UserApiTest.php

## 📝 Environment Variables

Key `.env` variables:

````env
# Application
APP_NAME=OnlyFix
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=onlyfix
DB_USERNAME=root
DB_PASSWORD=

# Authentication
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
SESSION_DRIVER=cookie
SESSION_LIFETIME=120

# Frontend (if using SPA)
## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Configure production database credentials
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed roles & permissions: `php artisan db:seed --class=RolePermissionSeeder --force`
- [ ] Optimize application: `php artisan optimize`
- [ ] Build frontend assets: `npm run build`
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up SSL/HTTPS certificate
- [ ] Configure CORS for API access
- [ ] Set up queue workers if using jobs
- [ ] Configure log rotation
- [ ] Set up monitoring and error tracking
- [ ] Configure backup strategy

### Recommended Services

- **Hosting**: Laravel Forge, DigitalOcean, AWS, Heroku
- **Database**: Managed MySQL (AWS RDS, DigitalOcean Managed Database)
- **Monitoring**: Laravel Telescope, Sentry, New Relic
- **Email**: Mailgun, SendGrid, AWS SES
### Problems

- Many-to-many with Tickets
- Fields: name, category, description, is_active

### Tickets

- Belongs to User (creator)
- Belongs to User (mechanic)
- Belongs to Car
- Many-to-many with Problems
- Fields: status, priority, description, timestamps

## 🔄 Workflow Example

### User Creates Ticket

```bash
# 1. User registers car
POST /api/cars
{
  "make": "Honda",
  "model": "Civic",
  "year": 2019,
  "license_plate": "XYZ-789"
}
## 📚 Additional Documentation

| File | Description |
|------|-------------|
| `API_TESTING_GUIDE.md` | Comprehensive API testing with Postman & Swagger |
| `ROLES_AND_PERMISSIONS.md` | Detailed role system documentation |
| `SEEDERS_AND_FACTORIES.md` | Mock data generation guide |
## 💡 Key Features Explained

### 1. Comprehensive Mock Data
The application includes extensive seeders that create a realistic testing environment:
- 56 common car problems categorized by system
- 35 service tickets across all workflow stages
- 29 vehicles with realistic make/model combinations
- 15 users with proper role assignments

Run `php artisan migrate:fresh --seed` to reset and repopulate anytime.

### 2. Advanced Filtering
All list endpoints support powerful filtering:
```bash
GET /api/tickets?status=open&priority=high&car_id=1
GET /api/problems?category=engine&is_active=1
GET /api/cars?user_id=2
````

### 3. Statistics Endpoints

Real-time analytics for business intelligence:

- Ticket statistics: Total, by status, average completion time
- Problem statistics: Most common issues, frequency analysis
- User statistics: Workload distribution for mechanics

### 4. Relationship Loading

Eager load relationships for optimized queries:

```bash
GET /api/tickets/1?include=user,car,mechanic,problems
```

### 5. Factory Methods

Easily generate test data in your own tests:

```php
$ticket = Ticket::factory()
    ->forUser($user)
    ->forCar($car)
    ->inProgress($mechanic)
    ->create();
```

## 🔧 Development Commands

```bash
# Database
php artisan migrate:fresh --seed    # Reset database with mock data
php artisan db:seed                  # Run seeders only
php artisan migrate:rollback         # Rollback last migration

# Testing
php artisan test                     # Run all tests
php artisan test --filter=TicketApi # Run specific tests
php artisan test --coverage          # Generate coverage report

# Optimization
php artisan optimize                 # Optimize application
php artisan optimize:clear           # Clear optimization caches
php artisan route:list               # List all routes
php artisan route:list --path=api   # List API routes only

# Development
php artisan serve                    # Start development server
npm run dev                          # Start Vite dev server
npm run build                        # Build for production
```

## 🎓 Learning Resources

- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Vue 3 Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [REST API Best Practices](https://restfulapi.net/)
- [OpenAPI Specification](https://swagger.io/specification/)

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/AmazingFeature`
3. Commit your changes: `git commit -m 'Add some AmazingFeature'`
4. Push to the branch: `git push origin feature/AmazingFeature`
5. Open a Pull Request

Please ensure:

- All tests pass: `php artisan test`
- Code follows PSR-12 standards
- New features include tests
- Documentation is updated

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

**Built with ❤️ using Laravel 12, Vue 3, and modern web technologies**

*OnlyFix - Streamlining car service management, one ticket at a time.*rk

- **MySQL 8** - Relational database
- **Laravel Sanctum** - API authentication
- **Spatie Laravel Permission** - Role & permission management
- **PHPUnit** - Testing framework

### Frontend

- **Vue 3** - Progressive JavaScript framework
- **Inertia.js** - Modern monolith architecture
- **Tailwind CSS 4** - Utility-first CSS framework
- **Reka UI** - Headless UI components
- **Vite** - Frontend build tool

### Development Tools

- **Composer** - PHP dependency management
- **npm** - JavaScript package management
- **Swagger UI** - API documentation
- **Postman** - API testing
- **Git** - Version control
  "priority": "high",
  "problem_ids": [5, 6]
  }

````

### Mechanic Processes Ticket

```bash
# 3. Mechanic accepts ticket
POST /api/tickets/1/accept

# 4. Mechanic starts work
POST /api/tickets/1/start

# 5. Mechanic completes work
POST /api/tickets/1/complete
````

### User Closes Ticket

```bash
# 6. User closes ticket
POST /api/tickets/1/close
```

## 📝 Environment Variables

Key `.env` variables:

```env
APP_NAME=OnlyFix
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_DATABASE=onlyfix

SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DRIVER=cookie
```

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed roles: `php artisan db:seed --class=RolePermissionSeeder --force`
- [ ] Clear caches: `php artisan optimize`
- [ ] Set up queue workers if needed
- [ ] Configure CORS for frontend
- [ ] Set up SSL/HTTPS

## 🤝 Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/AmazingFeature`
3. Commit changes: `git commit -m 'Add AmazingFeature'`
4. Push to branch: `git push origin feature/AmazingFeature`
5. Open Pull Request

## 📄 License

This project is open-sourced software licensed under the MIT license.

## 🆘 Support

### Common Issues

**Issue: 401 Unauthorized**

- Ensure you're sending the Bearer token
- Token may have expired, login again

**Issue: 403 Forbidden**

- Check user has correct role/permissions
- Verify resource ownership

**Issue: 422 Validation Error**

- Check request body matches required fields
- Verify data types and constraints

### Getting Help

- Check `storage/logs/laravel.log` for errors
- Review test files for expected behavior
- See `API_TESTING_GUIDE.md` for detailed examples

## 📚 Additional Documentation

- `API_TESTING_GUIDE.md` - Comprehensive API testing guide
- `ROLES_AND_PERMISSIONS.md` - Role and permission details
- `postman_collection.json` - Postman collection
- `openapi.yaml` - OpenAPI/Swagger specification

## 🎓 Learning Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [REST API Best Practices](https://restfulapi.net/)

---

Built with ❤️ using Laravel 11
