# Roles and Permissions Documentation

This application uses [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) for role-based access control.

## Roles

The system has three main roles in increasing order of power:

### 1. User

- **Purpose**: Regular users who can create and manage their own tickets
- **Permissions**:
    - `view own tickets` - Can view tickets they created
    - `create tickets` - Can create new tickets
    - `update own tickets` - Can update their own tickets
    - `delete own tickets` - Can delete their own tickets

### 2. Mechanic

- **Purpose**: Service providers who can view all tickets and choose which to accept
- **Permissions**: All user permissions plus:
    - `view all tickets` - Can view all tickets in the system
    - `accept tickets` - Can accept/assign tickets to themselves
    - `update any ticket` - Can update any ticket

### 3. Admin

- **Purpose**: System administrators with full control
- **Permissions**: All permissions including:
    - All mechanic permissions
    - `manage users` - Can create, update, and delete user accounts
    - `reset passwords` - Can reset passwords for any user
    - `manage roles` - Can assign/remove roles from users
    - `view all users` - Can view all user accounts
    - `delete any ticket` - Can delete any ticket

## Usage Examples

### In Controllers

```php
// Check if user has a specific role
if ($user->hasRole('admin')) {
    // Admin-specific code
}

// Check if user has any of the given roles
if ($user->hasAnyRole(['mechanic', 'admin'])) {
    // Code for mechanics and admins
}

// Check if user has a specific permission
if ($user->can('view all tickets')) {
    // Code for users who can view all tickets
}
```

### In Routes (Middleware)

```php
// Require specific role
Route::middleware(['role:admin'])->group(function () {
    // Admin-only routes
});

// Require specific permission
Route::middleware(['permission:manage users'])->group(function () {
    // Routes for users with manage users permission
});

// Require any of the given roles
Route::middleware(['role:mechanic|admin'])->group(function () {
    // Routes for mechanics and admins
});
```

### In Blade Views

```blade
@role('admin')
    <p>This is only visible to admins</p>
@endrole

@hasrole('mechanic')
    <p>This is only visible to mechanics</p>
@endhasrole

@can('manage users')
    <p>This user can manage users</p>
@endcan
```

### In Policies

Policies have been created for authorization:

**TicketPolicy**: Controls access to ticket operations

- `viewAny()` - Can view all tickets
- `view()` - Can view a specific ticket
- `create()` - Can create tickets
- `update()` - Can update a ticket
- `delete()` - Can delete a ticket
- `accept()` - Can accept/assign tickets

**UserPolicy**: Controls access to user management

- `viewAny()` - Can view all users (admin only)
- `view()` - Can view a user profile
- `update()` - Can update a user
- `delete()` - Can delete a user (admin only, not themselves)
- `resetPassword()` - Can reset user passwords (admin only)
- `manageRoles()` - Can assign/remove roles (admin only)

### In Frontend (Inertia/Vue)

User roles and permissions are automatically shared with the frontend via Inertia middleware:

```typescript
// In Vue components
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

// Check if user has a role
if (user.roles.includes('admin')) {
    // Admin-specific UI
}

// Check if user has a permission
if (user.permissions.includes('manage users')) {
    // Show user management UI
}
```

## Database Seeding

Test users with different roles are created by default:

```
Email: admin@example.com
Password: password
Role: admin

Email: mechanic@example.com
Password: password
Role: mechanic

Email: test@example.com
Password: password
Role: user
```

## Creating Users with Roles

### In Factories

```php
// Create user with specific role
User::factory()->asAdmin()->create();
User::factory()->asMechanic()->create();
User::factory()->asUser()->create();
```

### In Code

```php
// Create user and assign role
$user = User::create([...]);
$user->assignRole('user');

// Assign multiple roles
$user->assignRole(['user', 'mechanic']);

// Sync roles (removes all other roles)
$user->syncRoles(['admin']);

// Remove role
$user->removeRole('user');
```

## Assigning Permissions

```php
// Give permission directly to a user (rare, usually use roles)
$user->givePermissionTo('view all tickets');

// Give permission to a role
$role = Role::findByName('mechanic');
$role->givePermissionTo('accept tickets');

// Revoke permission
$user->revokePermissionTo('view all tickets');
```

## Cache Management

Spatie caches roles and permissions for performance. If you modify roles/permissions programmatically, clear the cache:

```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

Or via artisan:

```bash
php artisan permission:cache-reset
```

## Additional Commands

```bash
# Create a permission
php artisan permission:create-permission "permission name"

# Create a role
php artisan permission:create-role "role name"

# Show all roles and permissions
php artisan permission:show
```

## Future Ticket System Considerations

When implementing the ticket system:

1. **Ticket Model** should have:
    - `user_id` (creator)
    - `mechanic_id` (assigned mechanic, nullable)
    - `status` (open, in_progress, completed, etc.)

2. **Ticket Visibility**:
    - Users see only their tickets
    - Mechanics see all tickets
    - Admins see all tickets

3. **Ticket Assignment**:
    - Only mechanics and admins can accept/assign tickets
    - Use the `accept` permission to control this

4. **Authorization**:
    - Use TicketPolicy to control who can view/edit tickets
    - Check ownership: `$ticket->user_id === $user->id`
