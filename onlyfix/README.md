# OnlyFix - Car Service Management System

A comprehensive REST API for managing car service tickets, built with Laravel 11, featuring role-based access control, complete CRUD operations, and a robust testing suite.

## 🚀 Features

### Core Functionality

- **User Management** with role-based access control (User, Mechanic, Admin)
- **Car Management** - Users can register and manage their vehicles
- **Problem Catalog** - Standardized car problems database
- **Service Tickets** - Complete workflow from creation to completion
- **RESTful API** - Clean, well-documented endpoints
- **Authentication** - Laravel Sanctum API token authentication

### Roles & Permissions

- **User**: Manage own cars and tickets
- **Mechanic**: View all tickets, accept and complete work
- **Admin**: Full system access, user management, statistics

### Ticket Workflow

```
Open → Assigned → In Progress → Completed → Closed
```

## 📋 Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ or PostgreSQL
- Node.js 18+ (for frontend if needed)

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

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

### 5. Create Test Users (Optional)

```bash
php artisan tinker
```

```php
// Create admin
User::factory()->asAdmin()->create([
    'email' => 'admin@test.com',
    'password' => bcrypt('password')
]);

// Create mechanic
User::factory()->asMechanic()->create([
    'email' => 'mechanic@test.com',
    'password' => bcrypt('password')
]);

// Create regular user
User::factory()->asUser()->create([
    'email' => 'user@test.com',
    'password' => bcrypt('password')
]);

// Create some problems
Problem::factory()->count(10)->create();
```

### 6. Start the Server

```bash
php artisan serve
```

Your API is now available at `http://localhost:8000`

## 📚 API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentication

All endpoints (except `/api/health`, `/api/register`, `/api/login`) require a Bearer token:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

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
```

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

```bash
POST /api/cars
Authorization: Bearer YOUR_TOKEN
{
  "make": "Toyota",
  "model": "Camry",
  "year": 2020,
  "license_plate": "ABC-1234"
}
```

4. **Create a Ticket**

```bash
POST /api/tickets
Authorization: Bearer YOUR_TOKEN
{
  "car_id": 1,
  "description": "Engine making strange noise",
  "priority": "high",
  "problem_ids": [1, 2]
}
```

### Available Endpoints

#### Authentication

- `POST /api/register` - Register new user
- `POST /api/login` - Login
- `POST /api/logout` - Logout (revoke token)
- `GET /api/user` - Get authenticated user

#### Users

- `GET /api/users/me` - Get current user profile
- `GET /api/users` - List users (Admin only)
- `POST /api/users` - Create user (Admin only)
- `GET /api/users/{id}` - Get user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user (Admin only)
- `GET /api/users/mechanics` - List mechanics
- `GET /api/users/{id}/tickets` - Get user's tickets
- `GET /api/users/{id}/cars` - Get user's cars

#### Cars

- `GET /api/cars` - List cars
- `POST /api/cars` - Create car
- `GET /api/cars/{id}` - Get car
- `PUT /api/cars/{id}` - Update car
- `DELETE /api/cars/{id}` - Delete car
- `GET /api/cars/{id}/tickets` - Get car's tickets

#### Problems

- `GET /api/problems` - List problems
- `POST /api/problems` - Create problem (Mechanic/Admin)
- `GET /api/problems/{id}` - Get problem
- `PUT /api/problems/{id}` - Update problem (Mechanic/Admin)
- `DELETE /api/problems/{id}` - Delete problem (Admin only)
- `GET /api/problems/statistics` - Problem statistics (Mechanic/Admin)

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

- `GET /api/health` - API health check (no auth required)

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
php artisan test --filter=CarApiTest
php artisan test --filter=TicketApiTest
php artisan test --filter=UserApiTest
php artisan test --filter=ProblemApiTest
```

### With Coverage

```bash
php artisan test --coverage
```

### Test Database

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
4. Try: Users → Get Current User

**See `API_TESTING_GUIDE.md` for detailed instructions**

## 📊 Using Swagger/OpenAPI

### Online Editor

1. Visit https://editor.swagger.io/
2. Copy contents of `openapi.yaml`
3. Paste into editor
4. View rendered documentation

### VS Code Extension

1. Install "Swagger Viewer" extension
2. Open `openapi.yaml`
3. Right-click → "Preview Swagger"

## 🏗️ Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── CarController.php
│   │       ├── ProblemController.php
│   │       ├── TicketController.php
│   │       └── UserController.php
│   └── Middleware/
├── Models/
│   ├── Car.php
│   ├── Problem.php
│   ├── Ticket.php
│   └── User.php
└── Policies/
    ├── TicketPolicy.php
    └── UserPolicy.php

database/
├── factories/
│   ├── CarFactory.php
│   ├── ProblemFactory.php
│   ├── TicketFactory.php
│   └── UserFactory.php
├── migrations/
└── seeders/
    └── RolePermissionSeeder.php

tests/
└── Feature/
    ├── CarApiTest.php
    ├── ProblemApiTest.php
    ├── RolePermissionTest.php
    ├── TicketApiTest.php
    └── UserApiTest.php

routes/
├── api.php
└── web.php
```

## 🔐 Security Features

- **Authentication**: Laravel Sanctum token-based auth
- **Authorization**: Role-based permissions with Spatie Laravel Permission
- **Validation**: Comprehensive request validation
- **Policies**: Resource-level authorization
- **CSRF Protection**: On web routes
- **Rate Limiting**: API throttling

## 🎯 Database Schema

### Users

- Standard Laravel auth fields
- Role relationships (via Spatie Permission)

### Cars

- Belongs to User
- Has many Tickets
- Fields: make, model, year, license_plate, vin, color

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

# 2. User creates ticket
POST /api/tickets
{
  "car_id": 1,
  "description": "Brake pads need replacement",
  "priority": "high",
  "problem_ids": [5, 6]
}
```

### Mechanic Processes Ticket

```bash
# 3. Mechanic accepts ticket
POST /api/tickets/1/accept

# 4. Mechanic starts work
POST /api/tickets/1/start

# 5. Mechanic completes work
POST /api/tickets/1/complete
```

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
