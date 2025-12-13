# OnlyFix API - Quick Reference

## 🚀 Quick Start Commands

### Initial Setup

```bash
# Install dependencies
composer install

# Setup database
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder

# Start server
php artisan serve
```

### Create Test Users

```bash
php artisan tinker
```

```php
// Admin
User::factory()->asAdmin()->create(['email' => 'admin@test.com', 'password' => bcrypt('password')]);

// Mechanic
User::factory()->asMechanic()->create(['email' => 'mechanic@test.com', 'password' => bcrypt('password')]);

// Regular User
User::factory()->asUser()->create(['email' => 'user@test.com', 'password' => bcrypt('password')]);

// Problems
Problem::factory()->count(10)->create();
```

---

## 📍 API Endpoints Quick Reference

### Base URL

```
http://localhost:8000/api
```

### Authentication (No auth required)

```
POST   /api/register           Register new user
POST   /api/login              Login and get token
GET    /api/health             Health check
```

### Authenticated Endpoints (Require Bearer token)

#### Auth

```
POST   /api/logout             Logout (revoke token)
GET    /api/user               Get authenticated user
```

#### Users

```
GET    /api/users/me           Get my profile
GET    /api/users              List all users (Admin)
POST   /api/users              Create user (Admin)
GET    /api/users/{id}         Get user
PUT    /api/users/{id}         Update user
DELETE /api/users/{id}         Delete user (Admin)
GET    /api/users/mechanics    List mechanics
GET    /api/users/{id}/tickets User's tickets
GET    /api/users/{id}/cars    User's cars
```

#### Cars

```
GET    /api/cars               List cars
POST   /api/cars               Create car
GET    /api/cars/{id}          Get car
PUT    /api/cars/{id}          Update car
DELETE /api/cars/{id}          Delete car
GET    /api/cars/{id}/tickets  Car's tickets
```

#### Problems

```
GET    /api/problems           List problems
POST   /api/problems           Create problem (Mechanic/Admin)
GET    /api/problems/{id}      Get problem
PUT    /api/problems/{id}      Update problem (Mechanic/Admin)
DELETE /api/problems/{id}      Delete problem (Admin)
GET    /api/problems/statistics Problem stats (Mechanic/Admin)
```

#### Tickets

```
GET    /api/tickets            List tickets
POST   /api/tickets            Create ticket
GET    /api/tickets/{id}       Get ticket
PUT    /api/tickets/{id}       Update ticket
DELETE /api/tickets/{id}       Delete ticket
POST   /api/tickets/{id}/accept      Accept ticket (Mechanic)
POST   /api/tickets/{id}/start       Start work (Mechanic)
POST   /api/tickets/{id}/complete    Complete ticket (Mechanic)
POST   /api/tickets/{id}/close       Close ticket (User/Admin)
GET    /api/tickets/statistics       Ticket stats (Mechanic/Admin)
```

---

## 🔑 Authentication Flow

### 1. Register

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response:**

```json
{
  "message": "User registered successfully",
  "user": {...},
  "token": "1|xyz..."
}
```

### 2. Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Response:**

```json
{
  "message": "Login successful",
  "user": {...},
  "token": "2|abc..."
}
```

### 3. Use Token

```bash
curl -X GET http://localhost:8000/api/users/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 📋 Common Request Examples

### Create Car

```bash
POST /api/cars
{
  "make": "Toyota",
  "model": "Camry",
  "year": 2020,
  "license_plate": "ABC-1234",
  "vin": "1HGBH41JXMN109186",
  "color": "Blue"
}
```

### Create Ticket

```bash
POST /api/tickets
{
  "car_id": 1,
  "description": "Engine making strange noise when starting",
  "priority": "high",
  "problem_ids": [1, 2, 3],
  "problem_notes": [
    "Loud grinding sound",
    "Occurs when cold",
    "Getting worse"
  ]
}
```

### Filter Tickets

```bash
GET /api/tickets?status=open&priority=urgent&mechanic_id=2
```

### Search Problems

```bash
GET /api/problems?category=Engine&is_active=1&search=oil
```

---

## 👥 Role Permissions

### User

- ✅ Create and manage own cars
- ✅ Create and manage own tickets (only when open)
- ✅ View own resources
- ❌ Cannot view other users' data
- ❌ Cannot change ticket status

### Mechanic

- ✅ All User permissions
- ✅ View all tickets and cars
- ✅ Accept, start, and complete tickets
- ✅ Create and update problems
- ✅ View statistics
- ❌ Cannot delete problems
- ❌ Cannot manage users

### Admin

- ✅ All Mechanic permissions
- ✅ Full user management (create, update, delete)
- ✅ Delete any resource
- ✅ Update any ticket status
- ✅ View and manage everything
- ❌ Cannot delete themselves

---

## 🎯 Ticket Workflow

```
┌──────┐
│ OPEN │ ← User creates ticket
└──┬───┘
   │ Mechanic accepts
   ▼
┌──────────┐
│ ASSIGNED │
└────┬─────┘
     │ Mechanic starts work
     ▼
┌─────────────┐
│ IN_PROGRESS │
└──────┬──────┘
       │ Mechanic completes
       ▼
┌───────────┐
│ COMPLETED │
└─────┬─────┘
      │ User closes
      ▼
┌────────┐
│ CLOSED │
└────────┘
```

---

## 📊 Response Structures

### Success Response

```json
{
  "message": "Resource created successfully",
  "data": {
    "id": 1,
    ...
  }
}
```

### Error Response

```json
{
    "message": "Validation error message",
    "errors": {
        "field": ["Error description"]
    }
}
```

### Paginated Response

```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 5,
  "per_page": 15,
  "total": 73
}
```

---

## 🔍 Query Parameters

### Filtering

```
?status=open              Filter by status
?priority=high            Filter by priority
?category=Engine          Filter by category
?user_id=1               Filter by user
?mechanic_id=2           Filter by mechanic
?is_active=1             Filter active/inactive
```

### Search

```
?search=oil              Search by keyword
```

### Pagination

```
?page=2                  Page number (auto-included)
```

---

## 🧪 Testing

### Run Tests

```bash
# All tests
php artisan test

# Specific suite
php artisan test --filter=CarApiTest
php artisan test --filter=TicketApiTest
php artisan test --filter=UserApiTest
php artisan test --filter=ProblemApiTest

# With coverage
php artisan test --coverage
```

### Test Structure

```
tests/Feature/
├── CarApiTest.php          # Car CRUD and authorization
├── ProblemApiTest.php      # Problem management
├── TicketApiTest.php       # Ticket workflow
├── UserApiTest.php         # User management
└── RolePermissionTest.php  # Role/permission system
```

---

## 🐛 Debugging

### Check Logs

```bash
tail -f storage/logs/laravel.log
```

### Common HTTP Status Codes

- `200` OK - Success
- `201` Created - Resource created
- `401` Unauthorized - Missing or invalid token
- `403` Forbidden - No permission
- `404` Not Found - Resource doesn't exist
- `422` Validation Error - Invalid input
- `500` Server Error - Check logs

### Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 📦 Database Management

### Reset Database

```bash
php artisan migrate:fresh --seed
```

### Seed Specific Data

```bash
php artisan db:seed --class=RolePermissionSeeder
```

### Check Migrations

```bash
php artisan migrate:status
```

---

## 🛠️ Useful Commands

### Generate Models with Relations

```bash
php artisan tinker
```

```php
// Create user with cars and tickets
$user = User::factory()
    ->has(Car::factory()->count(2))
    ->has(Ticket::factory()->count(3))
    ->create();

// Create ticket with problems
$ticket = Ticket::factory()->create();
$problems = Problem::factory()->count(3)->create();
$ticket->problems()->attach($problems);
```

### Check Routes

```bash
php artisan route:list
php artisan route:list | grep api
```

### Generate API Documentation

```bash
# If using L5-Swagger (optional)
php artisan l5-swagger:generate
```

---

## 📚 Files Reference

### Documentation

- `README.md` - Main project documentation
- `API_TESTING_GUIDE.md` - Comprehensive testing guide
- `QUICK_REFERENCE.md` - This file
- `ROLES_AND_PERMISSIONS.md` - Role details

### API Specs

- `postman_collection.json` - Postman collection
- `openapi.yaml` - OpenAPI/Swagger spec

### Code Structure

- `routes/api.php` - API routes
- `app/Http/Controllers/Api/` - API controllers
- `app/Models/` - Eloquent models
- `app/Policies/` - Authorization policies
- `database/factories/` - Model factories
- `tests/Feature/` - Feature tests

---

## 💡 Tips

1. **Always use Accept header**: `Accept: application/json`
2. **Store token securely**: Never commit tokens to git
3. **Use environments**: Separate dev/staging/production
4. **Test authorization**: Verify users can't access others' data
5. **Check relationships**: Use `load()` or `with()` for eager loading
6. **Validate input**: All endpoints have validation
7. **Use factories**: Generate test data easily
8. **Read responses**: Error messages are descriptive

---

## 🔗 Quick Links

- Local API: http://localhost:8000/api
- Swagger Editor: https://editor.swagger.io/
- Postman: https://www.postman.com/downloads/
- Laravel Docs: https://laravel.com/docs

---

**Need more details?** See `API_TESTING_GUIDE.md` for comprehensive examples.
