# Frontend Development Guide - OnlyFix

## Overview

This application uses **Laravel Inertia.js** with **Vue 3** to create a seamless full-stack experience. All user-facing routes are defined in `routes/web.php` and render Vue components.

> **⚠️ IMPORTANT NOTE FOR BACKEND DEV:**
> The web routes currently reference controllers in `App\Http\Controllers\Api\` which return JSON responses.
> These need to be updated to return Inertia responses instead. Example:
>
> ```php
> // Instead of:
> return response()->json($cars);
>
> // Use:
> return Inertia::render('Cars/Index', [
>     'cars' => $cars,
>     'filters' => $request->only(['search', 'user_id'])
> ]);
> ```
>
> See "Controller Updates Needed" section below for details.

## Architecture

### Inertia.js Flow

```
User Request → Laravel Route → Controller → Inertia::render() → Vue Component
```

### Key Concepts

- **No API calls needed** - Data is passed as props from controllers to Vue components
- **Session-based auth** - Uses Laravel's built-in authentication
- **Automatic CSRF protection** - Handled by Laravel middleware
- **SPA experience** - Inertia provides client-side routing without page reloads

## Route Structure

All routes require authentication (`auth` middleware) unless otherwise noted.

### Public Routes

- `GET /` - Welcome page (`Welcome.vue`)

### Dashboard

- `GET /dashboard` - Main dashboard (`Dashboard.vue`)

### Cars Management

| Method | URL                  | Route Name     | Component             | Access                   |
| ------ | -------------------- | -------------- | --------------------- | ------------------------ |
| GET    | `/cars`              | `cars.index`   | `Cars/Index.vue`      | All authenticated users  |
| GET    | `/cars/create`       | `cars.create`  | `Cars/Create.vue`     | All authenticated users  |
| POST   | `/cars`              | `cars.store`   | N/A (form submission) | All authenticated users  |
| GET    | `/cars/{id}`         | `cars.show`    | `Cars/Show.vue`       | Owner, mechanics, admins |
| GET    | `/cars/{id}/edit`    | `cars.edit`    | `Cars/Edit.vue`       | Owner, admins            |
| PATCH  | `/cars/{id}`         | `cars.update`  | N/A (form submission) | Owner, admins            |
| DELETE | `/cars/{id}`         | `cars.destroy` | N/A (action)          | Owner, admins            |
| GET    | `/cars/{id}/tickets` | `cars.tickets` | `Cars/Tickets.vue`    | Owner, mechanics, admins |

### Tickets Management

| Method | URL                      | Route Name         | Component             | Access                       |
| ------ | ------------------------ | ------------------ | --------------------- | ---------------------------- |
| GET    | `/tickets`               | `tickets.index`    | `Tickets/Index.vue`   | All authenticated users      |
| GET    | `/tickets/create`        | `tickets.create`   | `Tickets/Create.vue`  | All authenticated users      |
| POST   | `/tickets`               | `tickets.store`    | N/A (form submission) | All authenticated users      |
| GET    | `/tickets/{id}`          | `tickets.show`     | `Tickets/Show.vue`    | Owner, mechanics, admins     |
| GET    | `/tickets/{id}/edit`     | `tickets.edit`     | `Tickets/Edit.vue`    | Owner (open tickets only)    |
| PATCH  | `/tickets/{id}`          | `tickets.update`   | N/A (form submission) | Owner, mechanics, admins     |
| DELETE | `/tickets/{id}`          | `tickets.destroy`  | N/A (action)          | Owner (open tickets), admins |
| POST   | `/tickets/{id}/accept`   | `tickets.accept`   | N/A (workflow action) | Mechanics, admins            |
| POST   | `/tickets/{id}/start`    | `tickets.start`    | N/A (workflow action) | Assigned mechanic, admins    |
| POST   | `/tickets/{id}/complete` | `tickets.complete` | N/A (workflow action) | Assigned mechanic, admins    |
| POST   | `/tickets/{id}/close`    | `tickets.close`    | N/A (workflow action) | Owner, admins                |

### Problems Management

| Method | URL                   | Route Name         | Component             | Access                  |
| ------ | --------------------- | ------------------ | --------------------- | ----------------------- |
| GET    | `/problems`           | `problems.index`   | `Problems/Index.vue`  | All authenticated users |
| GET    | `/problems/create`    | `problems.create`  | `Problems/Create.vue` | Mechanics, admins       |
| POST   | `/problems`           | `problems.store`   | N/A (form submission) | Mechanics, admins       |
| GET    | `/problems/{id}`      | `problems.show`    | `Problems/Show.vue`   | All authenticated users |
| GET    | `/problems/{id}/edit` | `problems.edit`    | `Problems/Edit.vue`   | Mechanics, admins       |
| PATCH  | `/problems/{id}`      | `problems.update`  | N/A (form submission) | Mechanics, admins       |
| DELETE | `/problems/{id}`      | `problems.destroy` | N/A (action)          | Admins only             |

### Users Management (Admin Only)

| Method | URL                | Route Name      | Component             | Access      |
| ------ | ------------------ | --------------- | --------------------- | ----------- |
| GET    | `/users`           | `users.index`   | `Users/Index.vue`     | Admins only |
| GET    | `/users/create`    | `users.create`  | `Users/Create.vue`    | Admins only |
| POST   | `/users`           | `users.store`   | N/A (form submission) | Admins only |
| GET    | `/users/{id}`      | `users.show`    | `Users/Show.vue`      | Admins only |
| GET    | `/users/{id}/edit` | `users.edit`    | `Users/Edit.vue`      | Admins only |
| PATCH  | `/users/{id}`      | `users.update`  | N/A (form submission) | Admins only |
| DELETE | `/users/{id}`      | `users.destroy` | N/A (action)          | Admins only |

### Statistics (Mechanics & Admins)

| Method | URL                    | Route Name            | Component                 | Access            |
| ------ | ---------------------- | --------------------- | ------------------------- | ----------------- |
| GET    | `/statistics/tickets`  | `statistics.tickets`  | `Statistics/Tickets.vue`  | Mechanics, admins |
| GET    | `/statistics/problems` | `statistics.problems` | `Statistics/Problems.vue` | Mechanics, admins |

### Mechanics List

| Method | URL          | Route Name        | Component             | Access            |
| ------ | ------------ | ----------------- | --------------------- | ----------------- |
| GET    | `/mechanics` | `mechanics.index` | `Mechanics/Index.vue` | Mechanics, admins |

## Component Structure

Create your Vue components in `resources/js/Pages/` following this structure:

```
resources/js/Pages/
├── Welcome.vue (already exists)
├── Dashboard.vue (already exists)
├── Cars/
│   ├── Index.vue          # List all cars
│   ├── Create.vue         # Create new car form
│   ├── Show.vue           # View single car details
│   ├── Edit.vue           # Edit car form
│   └── Tickets.vue        # View tickets for a car
├── Tickets/
│   ├── Index.vue          # List all tickets
│   ├── Create.vue         # Create new ticket form
│   ├── Show.vue           # View ticket details + workflow actions
│   └── Edit.vue           # Edit ticket form
├── Problems/
│   ├── Index.vue          # List all problems
│   ├── Create.vue         # Create new problem form
│   ├── Show.vue           # View problem details
│   └── Edit.vue           # Edit problem form
├── Users/
│   ├── Index.vue          # List all users (admin)
│   ├── Create.vue         # Create new user form (admin)
│   ├── Show.vue           # View user details (admin)
│   └── Edit.vue           # Edit user form (admin)
├── Statistics/
│   ├── Tickets.vue        # Ticket analytics dashboard
│   └── Problems.vue       # Problem analytics dashboard
└── Mechanics/
    └── Index.vue          # List of mechanics
```

## Data Props

Controllers pass data to Vue components as props. Here's what each component should expect:

### Cars/Index.vue

```javascript
defineProps({
    cars: Object, // Paginated collection { data: [], links: [], meta: {} }
    filters: Object, // { user_id: ?, search: ? }
});
```

### Cars/Show.vue

```javascript
defineProps({
    car: Object, // { id, make, model, year, license_plate, vin, color, user, tickets }
});
```

### Tickets/Index.vue

```javascript
defineProps({
    tickets: Object, // Paginated collection
    filters: Object, // { status: ?, priority: ?, car_id: ?, mechanic_id: ? }
});
```

### Tickets/Show.vue

```javascript
defineProps({
    ticket: Object, // { id, description, status, priority, car, user, mechanic, problems }
});
```

### Problems/Index.vue

```javascript
defineProps({
    problems: Object, // Paginated collection
    filters: Object, // { category: ?, is_active: ?, search: ? }
});
```

### Statistics/Tickets.vue

```javascript
defineProps({
    statistics: Object, // { total_tickets, by_status, by_priority, open_tickets, etc. }
});
```

## Navigation

Use Inertia's `Link` component or `router` for navigation:

### Using Link Component

```vue
<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="route('tickets.show', ticket.id)" class="btn">
        View Ticket
    </Link>
</template>
```

### Using Router (Programmatic)

```vue
<script setup>
import { router } from '@inertiajs/vue3';

const deleteTicket = (id) => {
    if (confirm('Are you sure?')) {
        router.delete(route('tickets.destroy', id));
    }
};
</script>
```

### Form Submissions

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    make: '',
    model: '',
    year: 2024,
    license_plate: '',
});

const submit = () => {
    form.post(route('cars.store'), {
        onSuccess: () => {
            // Handle success
        },
        onError: () => {
            // Handle validation errors
        },
    });
};
</script>
```

## Route Helper

Laravel's `route()` helper is available in Vue components via Ziggy:

```vue
<template>
    <Link :href="route('cars.show', { car: 123 })">View Car</Link>
</template>
```

## User Roles & Permissions

Check user roles/permissions in components:

```vue
<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() =>
    user.value.roles.some((role) => role.name === 'admin'),
);
const isMechanic = computed(() =>
    user.value.roles.some((role) => role.name === 'mechanic'),
);
</script>

<template>
    <div v-if="isAdmin">
        <!-- Admin only content -->
    </div>
</template>
```

## Workflow Status Badge Colors

Suggested color scheme for ticket statuses:

- **open** - Blue/Info
- **assigned** - Yellow/Warning
- **in_progress** - Purple/Primary
- **completed** - Green/Success
- **closed** - Gray/Secondary

## Priority Badge Colors

Suggested color scheme for ticket priorities:

- **urgent** - Red (bg-red-100, text-red-800)
- **high** - Orange (bg-orange-100, text-orange-800)
- **medium** - Yellow (bg-yellow-100, text-yellow-800)
- **low** - Green (bg-green-100, text-green-800)

## Testing Your Components

All routes have corresponding tests in `tests/Feature/Web/`. Run:

```bash
php artisan test --filter=WebRoutesTest
```

## Next Steps

1. ✅ Routes are defined in `routes/web.php`
2. ✅ Controllers exist and return Inertia responses (need to be updated)
3. 🔄 Create Vue components in `resources/js/Pages/`
4. 🔄 Style with Tailwind CSS (already configured)
5. 🔄 Add form validation UI
6. 🔄 Add loading states and transitions

## Common Patterns

### List Page (Index)

- Display paginated table/grid
- Include search/filter form
- Link to show/edit pages
- Include create button (if authorized)

### Show Page

- Display all resource details
- Include related data (relationships)
- Action buttons (edit, delete, workflow actions)
- Breadcrumbs for navigation

### Create/Edit Forms

- Use Inertia forms with error handling
- Display validation errors below fields
- Include cancel button
- Show loading state on submit

### Workflow Actions

- Display current status badge
- Show available actions based on status and role
- Confirm destructive actions
- Show success/error messages

## Resources

- [Inertia.js Docs](https://inertiajs.com)
- [Vue 3 Docs](https://vuejs.org)
- [Tailwind CSS Docs](https://tailwindcss.com)
- [Laravel Docs](https://laravel.com/docs)

## Controller Updates Needed (Backend Dev TODO)

The controllers in `app/Http/Controllers/Api/` need to detect whether the request is from Inertia and return appropriate responses:

### Option 1: Check Request Type in Existing Controllers

```php
public function index(Request $request)
{
    $cars = Car::with(['user', 'tickets'])->paginate(15);

    // Check if request is from Inertia
    if ($request->header('X-Inertia')) {
        return Inertia::render('Cars/Index', [
            'cars' => $cars,
            'filters' => $request->only(['search', 'user_id'])
        ]);
    }

    // API response
    return response()->json($cars);
}
```

### Option 2: Create Separate Web Controllers (Recommended)

Create controllers in `app/Http/Controllers/` (without Api folder) that only return Inertia responses:

```php
// app/Http/Controllers/CarController.php
namespace App\Http\Controllers;

use Inertia\Inertia;

class CarController extends Controller
{
    public function index(Request $request)
    {
        // Reuse the same query logic from API controller
        $cars = Car::with(['user', 'tickets'])->paginate(15);

        return Inertia::render('Cars/Index', [
            'cars' => $cars,
            'filters' => $request->only(['search', 'user_id'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Cars/Create', [
            'users' => User::all() // If admin creating car for someone
        ]);
    }

    // ... other methods
}
```

### Controllers That Need Updates:

1. ✅ `CarController` - 8 methods (index, create, store, show, edit, update, destroy, tickets)
2. ✅ `TicketController` - 11 methods (index, create, store, show, edit, update, destroy, accept, startWork, complete, close)
3. ✅ `ProblemController` - 6 methods (index, create, store, show, edit, update, destroy)
4. ✅ `UserController` - 8 methods (index, create, store, show, edit, update, destroy, mechanics)

### Data to Pass to Components:

#### Cars/Index.vue

```php
return Inertia::render('Cars/Index', [
    'cars' => $cars, // Paginated
    'filters' => $request->only(['search', 'user_id']),
    'can' => [
        'createCar' => true // Everyone can create cars for themselves
    ]
]);
```

#### Tickets/Show.vue

```php
return Inertia::render('Tickets/Show', [
    'ticket' => $ticket->load(['car', 'user', 'mechanic', 'problems']),
    'can' => [
        'update' => $request->user()->can('update', $ticket),
        'delete' => $request->user()->can('delete', $ticket),
        'accept' => $request->user()->hasAnyRole(['mechanic', 'admin']) && $ticket->status === 'open',
        'start' => $request->user()->hasAnyRole(['mechanic', 'admin']) && $ticket->status === 'assigned',
        'complete' => $request->user()->hasAnyRole(['mechanic', 'admin']) && $ticket->status === 'in_progress',
        'close' => ($ticket->user_id === $request->user()->id || $request->user()->hasRole('admin'))
    ]
]);
```

---

**Questions?** Contact the backend developer for clarification on data structures or API behavior.
