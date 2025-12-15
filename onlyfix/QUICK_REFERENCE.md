# Spatie Role-Based Authentication - Quick Reference

## ✅ Implementation Complete

### Test Users (Credentials)

```
Admin:    admin@example.com / password
Mechanic: mechanic@example.com / password
User:     test@example.com / password
```

## Roles & Permissions

### User (Basic)

- view own tickets
- create tickets
- update own tickets
- delete own tickets

### Mechanic (Extended)

- All User permissions +
- view all tickets
- accept tickets
- update any ticket

### Admin (Full Access)

- All Mechanic permissions +
- manage users
- reset passwords
- manage roles
- view all users
- delete any ticket

## Backend Usage

### In Controllers

```php
// Check role
if ($user->hasRole('admin')) { }
if ($user->hasAnyRole(['mechanic', 'admin'])) { }

// Check permission
if ($user->can('view all tickets')) { }

// Use policies
$this->authorize('update', $ticket);
$this->authorize('manageUsers', $user);
```

### In Routes

```php
// Protect routes with middleware
Route::middleware(['role:admin'])->group(function () {
    // Admin only routes
});

Route::middleware(['permission:manage users'])->group(function () {
    // User management routes
});
```

### Creating Users with Roles

```php
// Using factories
User::factory()->asAdmin()->create();
User::factory()->asMechanic()->create();
User::factory()->asUser()->create();

// Manually
$user = User::create([...]);
$user->assignRole('mechanic');
```

## Frontend Usage

### In Vue Components

```typescript
import { useAuth } from '@/composables/useAuth'

const {
  isAdmin,
  isMechanic,
  canViewAllTickets,
  hasPermission
} = useAuth()

// Conditionally render
<div v-if="isAdmin">Admin Panel</div>
<button v-if="canViewAllTickets">View All</button>
```

### Available Composable Methods

- `isAdmin`, `isMechanic`, `isUser` - Role checks
- `hasRole(role)` - Check specific role
- `hasPermission(permission)` - Check permission
- `canViewAllTickets` - Can see all tickets
- `canManageUsers` - Can manage users
- `canAcceptTickets` - Can accept tickets

## Files Modified/Created

### Models & Factories

- ✅ `app/Models/User.php` - Added HasRoles trait
- ✅ `database/factories/UserFactory.php` - Role methods

### Seeders & Migrations

- ✅ `database/seeders/RolePermissionSeeder.php` - Seeds roles/permissions
- ✅ `database/seeders/DatabaseSeeder.php` - Creates test users
- ✅ `database/migrations/*_create_permission_tables.php` - Permission tables

### Policies

- ✅ `app/Policies/TicketPolicy.php` - Ticket authorization
- ✅ `app/Policies/UserPolicy.php` - User management authorization

### Middleware & Config

- ✅ `bootstrap/app.php` - Registered role middleware
- ✅ `app/Http/Middleware/HandleInertiaRequests.php` - Share roles/permissions
- ✅ `config/permission.php` - Spatie config

### Frontend

- ✅ `resources/js/composables/useAuth.ts` - Auth helper composable
- ✅ `resources/js/types/index.d.ts` - Added roles/permissions types

### Controllers

- ✅ `app/Http/Controllers/RoleController.php` - Example controller

### Tests

- ✅ `tests/Feature/RolePermissionTest.php` - 10 passing tests

### Documentation

- ✅ `ROLES_AND_PERMISSIONS.md` - Complete documentation

## Commands

```bash
# Clear permission cache
php artisan permission:cache-reset

# Run tests
php artisan test --filter=RolePermissionTest

# Seed roles/permissions
php artisan db:seed --class=RolePermissionSeeder

# Full database refresh with roles
php artisan migrate:fresh --seed
```

## Next Steps for Ticket System

When implementing tickets:

1. **Create Ticket Model**

    ```php
    // Migration fields
    - user_id (creator)
    - mechanic_id (assigned to, nullable)
    - title, description
    - status (enum: open, in_progress, completed)
    ```

2. **Apply TicketPolicy**

    ```php
    // In TicketController
    $this->authorize('viewAny', Ticket::class);
    $this->authorize('update', $ticket);
    ```

3. **Scope Queries by Role**

    ```php
    // Users see only their tickets
    if ($user->hasRole('user')) {
        $tickets = Ticket::where('user_id', $user->id)->get();
    }

    // Mechanics/admins see all
    if ($user->hasAnyRole(['mechanic', 'admin'])) {
        $tickets = Ticket::all();
    }
    ```

## Support

- **Documentation**: See `ROLES_AND_PERMISSIONS.md`
- **Spatie Docs**: https://spatie.be/docs/laravel-permission
- **Tests**: Run `php artisan test --filter=RolePermissionTest`
