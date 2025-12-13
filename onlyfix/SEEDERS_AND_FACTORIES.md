# Database Seeders and Factories - Complete Documentation

## Overview

Comprehensive seeders and factories have been created for the OnlyFix application to provide realistic mock data for API testing and development.

## Created Files

### Seeders

1. **ProblemSeeder.php** - Seeds 56 realistic car problems across 8 categories
2. **CarSeeder.php** - Seeds cars for all users with realistic data
3. **TicketSeeder.php** - Seeds tickets with various statuses and problem associations
4. **RolePermissionSeeder.php** - Already existed, seeds roles and permissions
5. **DatabaseSeeder.php** - Updated to orchestrate all seeders

### Factories

All factories have been enhanced with realistic data generation:

1. **UserFactory.php** - Enhanced with role assignment methods
2. **CarFactory.php** - Enhanced with 50+ realistic car make/model combinations
3. **ProblemFactory.php** - Already existed with good structure
4. **TicketFactory.php** - Already existed with comprehensive status methods

## Seeded Data Summary

### Users (15 total)

- **Admin**: admin@example.com (password: password)
- **Mechanics**:
    - mechanic@example.com (password: password)
    - sarah.johnson@example.com (password: password)
    - mike.davis@example.com (password: password)
- **Regular Users**:
    - test@example.com (password: password)
    - 10 additional random users

### Cars (29 total)

- Each user has 1-4 cars
- Realistic makes/models (Toyota, Honda, Ford, BMW, etc.)
- Years ranging from 2000-2025
- Proper VIN and license plate formats
- Variety of colors

### Problems (56 total)

Problems organized by category:

- **Engine** (7): Oil leaks, overheating, misfires, check engine light, etc.
- **Transmission** (5): Slipping, hard shifting, fluid leaks, grinding
- **Electrical** (7): Battery, alternator, starter, lighting issues
- **Brakes** (7): Squeaking, grinding, soft pedal, ABS issues
- **Suspension** (6): Rough ride, clunking, uneven wear
- **Steering** (6): Hard steering, loose steering, vibrations
- **Body** (6): Rust, dents, scratches, door alignment
- **Other** (10): AC, heater, windows, exhaust, maintenance
- **Inactive** (2): Historical problems for testing

### Tickets (35 total)

Distribution by status:

- **Open** (5): New tickets awaiting assignment
- **Assigned** (4): Assigned to mechanic but not started
- **In Progress** (6): Currently being worked on
- **Completed** (8): Work done, awaiting closure
- **Closed** (12): Fully resolved tickets

### Ticket-Problem Relationships (63 total)

- Each ticket has 1-4 associated problems
- Includes detailed notes for each problem
- Realistic descriptions based on status

## Factory Features

### UserFactory

```php
User::factory()->asAdmin()->create();
User::factory()->asMechanic()->create();
User::factory()->asUser()->create();
User::factory()->withTwoFactor()->create();
User::factory()->unverified()->create();
```

### CarFactory

```php
Car::factory()->forUser($user)->create();
Car::factory()->old()->create();        // 2000-2010
Car::factory()->newer()->create();      // 2020-2025
Car::factory()->withMake('Toyota')->create();
```

### TicketFactory

```php
Ticket::factory()->open()->create();
Ticket::factory()->assigned($mechanic)->create();
Ticket::factory()->inProgress($mechanic)->create();
Ticket::factory()->completed($mechanic)->create();
Ticket::factory()->closed($mechanic)->create();
Ticket::factory()->urgent()->create();
Ticket::factory()->forUser($user)->create();
Ticket::factory()->forCar($car)->create();
```

### ProblemFactory

```php
Problem::factory()->create();
Problem::factory()->inactive()->create();
Problem::factory()->category('engine')->create();
```

## Running the Seeders

### Fresh Migration with Seeding

```bash
php artisan migrate:fresh --seed
```

### Run Specific Seeders

```bash
php artisan db:seed --class=ProblemSeeder
php artisan db:seed --class=CarSeeder
php artisan db:seed --class=TicketSeeder
```

### Run All Seeders

```bash
php artisan db:seed
```

## Testing Scenarios

The seeded data supports various testing scenarios:

### User Scenarios

1. **Admin User**: Can manage all users, tickets, and system settings
2. **Mechanic User**: Can view all tickets, accept and work on tickets
3. **Regular User**: Can create tickets for their cars and view their own tickets

### Ticket Scenarios

1. **New Ticket Flow**: Open → Assigned → In Progress → Completed → Closed
2. **Multiple Problems**: Tickets with various problem combinations
3. **Priority Levels**: Low, medium, high, and urgent tickets
4. **Historical Data**: Closed tickets from up to 6 months ago

### Car Scenarios

1. **Multiple Cars per User**: Users with 1-4 cars each
2. **Various Makes/Models**: 50+ realistic car combinations
3. **Age Range**: Cars from 2000 to 2025
4. **Service History**: Through ticket relationships

### Problem Scenarios

1. **Active Problems**: 54 current problem types
2. **Inactive Problems**: 2 historical problem types
3. **Category Filtering**: 8 different categories
4. **Problem Relationships**: Track which problems affect which cars

## API Testing Examples

### Get User's Cars

```bash
GET /api/users/{user_id}/cars
```

### Get User's Tickets

```bash
GET /api/tickets?user_id={user_id}
```

### Get Open Tickets (Mechanic View)

```bash
GET /api/tickets?status=open
```

### Get Car Service History

```bash
GET /api/cars/{car_id}/tickets?status=closed
```

### Get Problems by Category

```bash
GET /api/problems?category=engine&is_active=1
```

### Create New Ticket

```bash
POST /api/tickets
{
    "car_id": 1,
    "description": "Engine making strange noise",
    "priority": "high",
    "problem_ids": [1, 6]
}
```

## Data Relationships

### User → Cars → Tickets → Problems

- Users own multiple cars
- Cars have multiple tickets
- Tickets have multiple problems
- Complete relationship chain for comprehensive testing

### Mechanic → Assigned Tickets

- Mechanics can be assigned to tickets
- Track accepted_at and completed_at timestamps
- Different statuses throughout workflow

## Benefits for API Testing

1. **Realistic Data**: All data reflects real-world scenarios
2. **Relationships**: Full relational data for complex queries
3. **Status Variety**: Tickets in all possible states
4. **Historical Data**: Past tickets for reporting features
5. **Multiple Users**: Different roles for permission testing
6. **Edge Cases**: Includes inactive problems, old cars, etc.

## Customization

### Add More Data

Edit the seeders to increase counts:

```php
// In TicketSeeder.php
for ($i = 0; $i < 10; $i++) { // Change from 5 to 10
    // Create more open tickets
}
```

### Add Custom Problems

Edit `ProblemSeeder.php` to add domain-specific problems.

### Adjust User Distribution

Edit `DatabaseSeeder.php` to change the number of users per role.

## Notes

- All passwords for seeded users are: `password`
- VINs and license plates are randomly generated but follow proper formats
- Problem descriptions are realistic and category-appropriate
- Ticket descriptions vary by status for authentic testing
- Relationships are properly maintained with pivot table data
