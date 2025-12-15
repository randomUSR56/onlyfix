# OnlyFix API Testing Guide

## Table of Contents

1. [Using Postman](#using-postman)
2. [Using Swagger UI](#using-swagger-ui)
3. [Testing Workflow](#testing-workflow)
4. [Common Issues & Solutions](#common-issues--solutions)

---

## Using Postman

### Setup Postman

1. **Install Postman**
    - Download from: https://www.postman.com/downloads/
    - Install and create a free account (optional but recommended)

2. **Import the Collection**
    - Open Postman
    - Click "Import" button (top left)
    - Select the `postman_collection.json` file from your project root
    - Collection "OnlyFix API" will appear in your collections

3. **Set Up Environment Variables**
    - Click on "Environments" in the left sidebar
    - Create new environment called "OnlyFix Local"
    - Add these variables:
        ```
        base_url: http://localhost:8000
        access_token: (leave empty - will be set automatically)
        user_id: 1
        car_id: 1
        problem_id: 1
        ticket_id: 1
        ```
    - Save and select this environment from the dropdown in top right

### Testing Authentication

1. **Start your Laravel server**

    ```bash
    php artisan serve
    ```

2. **Register a New User**
    - Navigate to: Authentication → Register
    - Click "Send"
    - You should see a 201 response with user data

3. **Login**
    - Navigate to: Authentication → Login
    - Update the email/password in the body if needed
    - Click "Send"
    - The response will include a token
    - **Important**: The token is automatically saved to `access_token` variable

4. **Test Protected Endpoint**
    - Navigate to: Users → Get Current User
    - Click "Send"
    - You should see your user data (200 response)

### Testing the Complete Workflow

#### Step 1: Create Test Users

First, seed your database with roles:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

Then create users for different roles using Tinker:

```bash
php artisan tinker
```

```php
// Create admin
$admin = User::factory()->asAdmin()->create(['email' => 'admin@test.com']);

// Create mechanic
$mechanic = User::factory()->asMechanic()->create(['email' => 'mechanic@test.com']);

// Create regular user
$user = User::factory()->asUser()->create(['email' => 'user@test.com']);
```

#### Step 2: Test as Regular User

1. **Login as User**

    ```json
    POST /login
    {
      "email": "user@test.com",
      "password": "password"
    }
    ```

2. **Create a Car**

    ```
    POST /api/cars
    ```

    - Use the pre-filled body
    - Copy the returned `id` to `car_id` variable

3. **Create Problems** (Login as mechanic first)
    - Switch to mechanic login
    - Navigate to: Problems → Create Problem
    - Create 2-3 problems
    - Note their IDs

4. **Create a Ticket** (Back to user)
    - Login as user again
    - Navigate to: Tickets → Create Ticket
    - Update `car_id` and `problem_ids` in body
    - Send request

#### Step 3: Test as Mechanic

1. **Login as Mechanic**

    ```json
    POST /login
    {
      "email": "mechanic@test.com",
      "password": "password"
    }
    ```

2. **View All Tickets**

    ```
    GET /api/tickets
    ```

    - You should see all tickets including the one just created

3. **Accept a Ticket**

    ```
    POST /api/tickets/{ticket_id}/accept
    ```

    - Update `ticket_id` variable with actual ticket ID
    - Ticket status should change to "assigned"

4. **Start Work**

    ```
    POST /api/tickets/{ticket_id}/start
    ```

    - Status changes to "in_progress"

5. **Complete Ticket**
    ```
    POST /api/tickets/{ticket_id}/complete
    ```

    - Status changes to "completed"

#### Step 4: Test as Admin

1. **Login as Admin**

    ```json
    POST /login
    {
      "email": "admin@test.com",
      "password": "password"
    }
    ```

2. **Create a New Mechanic**

    ```
    POST /api/users
    ```

    - Admins can create users with any role

3. **View Statistics**
    - GET /api/tickets/statistics
    - GET /api/problems/statistics

### Using Query Parameters

Many endpoints support filtering. In Postman:

1. Click on the "Params" tab below the URL
2. Add key-value pairs:

**Example - Filter Tickets:**

```
GET /api/tickets
Params:
  status: open
  priority: high
```

**Example - Search Users:**

```
GET /api/users
Params:
  role: mechanic
  search: john
```

### Collection Runner (Automated Testing)

1. Click the collection name "OnlyFix API"
2. Click "Run" button
3. Select which requests to run
4. Click "Run OnlyFix API"
5. View results of all tests in sequence

---

## Using Swagger UI

### Setup Swagger UI

1. **Install Swagger UI Online Tool**
    - Visit: https://editor.swagger.io/
    - Or use VS Code extension: "Swagger Viewer"

2. **Load the OpenAPI Spec**
    - Copy contents of `openapi.yaml`
    - Paste into Swagger Editor
    - Or if using VS Code extension, right-click the file → "Preview Swagger"

### Using Swagger Editor Online

1. **Visit** https://editor.swagger.io/
2. **Clear** the default content
3. **Paste** your `openapi.yaml` content
4. **See** the rendered documentation on the right

### Testing with Swagger UI

1. **Set Authorization**
    - Click "Authorize" button at top
    - Enter your bearer token: `Bearer YOUR_TOKEN_HERE`
    - Click "Authorize" then "Close"

2. **Make Requests**
    - Expand any endpoint
    - Click "Try it out"
    - Fill in parameters/body
    - Click "Execute"
    - See response below

### Local Swagger UI Setup (Optional)

For a better local experience:

1. **Install L5-Swagger package:**

    ```bash
    composer require darkaonline/l5-swagger
    php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
    ```

2. **Generate documentation:**

    ```bash
    php artisan l5-swagger:generate
    ```

3. **View at:** http://localhost:8000/api/documentation

---

## Testing Workflow

### Complete End-to-End Test

Here's a complete workflow to test all functionality:

```
1. SETUP
   ✓ Run migrations: php artisan migrate:fresh
   ✓ Seed roles: php artisan db:seed --class=RolePermissionSeeder
   ✓ Create test users (use tinker as shown above)

2. USER WORKFLOW
   ✓ Login as user
   ✓ Create car
   ✓ View my cars
   ✓ Create ticket for my car
   ✓ View my tickets
   ✓ Update my ticket (only if still open)

3. MECHANIC WORKFLOW
   ✓ Login as mechanic
   ✓ View all tickets
   ✓ Filter tickets by status
   ✓ Accept an open ticket
   ✓ Start work on ticket
   ✓ View ticket statistics
   ✓ Complete ticket

4. ADMIN WORKFLOW
   ✓ Login as admin
   ✓ Create new mechanic user
   ✓ View all users
   ✓ Create new problem
   ✓ View problem statistics
   ✓ Update any ticket
   ✓ Delete user (not self)

5. NEGATIVE TESTS
   ✓ Try to access endpoints without token (401)
   ✓ Try user actions as non-owner (403)
   ✓ Try admin actions as user (403)
   ✓ Try invalid data (422 validation errors)
```

---

## Common Issues & Solutions

### Issue: 401 Unauthenticated

**Solution:**

- Make sure you've logged in
- Token should be in Authorization header as `Bearer YOUR_TOKEN`
- Check token hasn't expired

### Issue: 403 Forbidden

**Solution:**

- Check you have the correct role for this action
- Users can only access their own resources
- Mechanics can view all but limited write access
- Only admins have full access

### Issue: 422 Validation Error

**Solution:**

- Check all required fields are present
- Verify data types (integers, strings, etc.)
- Check unique constraints (email, license_plate, etc.)

### Issue: 404 Not Found

**Solution:**

- Verify the resource ID exists
- Check the URL is correct
- Ensure you have permission to view that resource

### Issue: 500 Server Error

**Solution:**

- Check Laravel logs: `storage/logs/laravel.log`
- Common causes:
    - Database connection issues
    - Missing relationships
    - Invalid data types

---

## API Testing Checklist

### Before Testing

- [ ] Database migrated and seeded
- [ ] Laravel server running (`php artisan serve`)
- [ ] Test users created for each role
- [ ] Postman collection imported
- [ ] Environment variables configured

### Basic Tests

- [ ] Health check endpoint works
- [ ] Register new user
- [ ] Login and receive token
- [ ] Token auto-saves in Postman
- [ ] Get current user profile

### CRUD Operations

- [ ] Create resource
- [ ] Read/list resources
- [ ] Update resource
- [ ] Delete resource
- [ ] Pagination works

### Authorization Tests

- [ ] User can access own resources
- [ ] User cannot access others' resources
- [ ] Mechanic has elevated access
- [ ] Admin has full access

### Business Logic Tests

- [ ] Ticket workflow (open → assigned → in_progress → completed → closed)
- [ ] Problem assignment to tickets
- [ ] Car-ticket relationships
- [ ] User-car relationships

---

## Quick Start Commands

### Database Setup

```bash
# Fresh start
php artisan migrate:fresh --seed

# Or step by step
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

### Create Test Data

```bash
php artisan tinker
```

```php
// Create admin
$admin = User::factory()->asAdmin()->create([
    'email' => 'admin@test.com',
    'password' => bcrypt('password')
]);

// Create mechanic
$mechanic = User::factory()->asMechanic()->create([
    'email' => 'mechanic@test.com',
    'password' => bcrypt('password')
]);

// Create user with car and ticket
$user = User::factory()
    ->asUser()
    ->has(Car::factory()->count(2))
    ->create([
        'email' => 'user@test.com',
        'password' => bcrypt('password')
    ]);

// Create some problems
Problem::factory()->count(10)->create();
```

### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=CarApiTest

# Run with coverage
php artisan test --coverage
```

---

## Tips for Effective Testing

1. **Use Collection Variables** - Store IDs from responses for use in subsequent requests
2. **Test Negative Cases** - Don't just test happy paths
3. **Check Response Structure** - Verify the JSON structure matches documentation
4. **Test Pagination** - Create many records and test pagination
5. **Test Filters** - Try different query parameter combinations
6. **Monitor Logs** - Keep `storage/logs/laravel.log` open while testing
7. **Use Environments** - Create separate environments for dev/staging/production

---

## Need Help?

- Check the API responses - they include helpful error messages
- Look at Laravel logs: `storage/logs/laravel.log`
- Review the controller code for business logic
- Check the policies for authorization rules
- Refer to the test files for expected behavior

---

Happy Testing! 🚀
