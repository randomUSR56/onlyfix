<?php

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Filtering - Ticket Filtering', function () {
    test('tickets can be filtered by status', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Ticket::factory()->count(3)->open()->create();
        Ticket::factory()->count(2)->assigned()->create();
        Ticket::factory()->count(4)->inProgress()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets?status=open');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('tickets can be filtered by priority', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Ticket::factory()->count(2)->urgent()->create();
        Ticket::factory()->count(3)->high()->create();
        Ticket::factory()->count(4)->low()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets?priority=urgent');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    test('tickets can be filtered by user_id', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        Ticket::factory()->count(3)->forCar($car)->create();
        Ticket::factory()->count(2)->create(); // Other users

        $response = $this->actingAs($admin)->getJson("/api/tickets?user_id={$user->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('tickets can be filtered by car_id', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->create();

        Ticket::factory()->count(4)->forCar($car)->create();
        Ticket::factory()->count(3)->create(); // Other cars

        $response = $this->actingAs($mechanic)->getJson("/api/tickets?car_id={$car->id}");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    });

    test('tickets can be filtered by mechanic_id', function () {
        $admin = User::factory()->asAdmin()->create();
        $mechanic = User::factory()->asMechanic()->create();

        Ticket::factory()->count(3)->assigned($mechanic)->create();
        Ticket::factory()->count(2)->create(); // Unassigned or other mechanics

        $response = $this->actingAs($admin)->getJson("/api/tickets?mechanic_id={$mechanic->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('multiple filters can be combined', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        Ticket::factory()->count(2)->forCar($car)->create(['status' => 'open', 'priority' => 'high']);
        Ticket::factory()->count(1)->forCar($car)->create(['status' => 'open', 'priority' => 'low']);
        Ticket::factory()->count(1)->forCar($car)->create(['status' => 'assigned', 'priority' => 'high']);

        $response = $this->actingAs($mechanic)->getJson("/api/tickets?status=open&priority=high&car_id={$car->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });
});

describe('Filtering - Problem Filtering', function () {
    test('problems can be filtered by category', function () {
        $user = User::factory()->asUser()->create();

        Problem::factory()->count(3)->create(['category' => 'engine']);
        Problem::factory()->count(2)->create(['category' => 'brakes']);

        $response = $this->actingAs($user)->getJson('/api/problems?category=engine');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('problems can be filtered by active status', function () {
        $user = User::factory()->asUser()->create();

        Problem::factory()->count(4)->create(['is_active' => true]);
        Problem::factory()->count(2)->create(['is_active' => false]);

        $response = $this->actingAs($user)->getJson('/api/problems?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    });

    test('problems can be searched by name', function () {
        $user = User::factory()->asUser()->create();

        Problem::factory()->create(['name' => 'Engine oil leak']);
        Problem::factory()->create(['name' => 'Brake pad wear']);
        Problem::factory()->create(['name' => 'Engine overheating']);

        $response = $this->actingAs($user)->getJson('/api/problems?search=engine');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });
});

describe('Filtering - Car Filtering', function () {
    test('cars can be filtered by user_id', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();

        Car::factory()->count(3)->forUser($user)->create();
        Car::factory()->count(2)->create(); // Other users

        $response = $this->actingAs($admin)->getJson("/api/cars?user_id={$user->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('cars can be searched by make', function () {
        $admin = User::factory()->asAdmin()->create();

        Car::factory()->create(['make' => 'Toyota', 'model' => 'Camry']);
        Car::factory()->create(['make' => 'Toyota', 'model' => 'Corolla']);
        Car::factory()->create(['make' => 'Honda', 'model' => 'Civic']);

        $response = $this->actingAs($admin)->getJson('/api/cars?search=Toyota');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    test('cars can be searched by license plate', function () {
        $admin = User::factory()->asAdmin()->create();

        Car::factory()->create(['license_plate' => 'ABC-123']);
        Car::factory()->create(['license_plate' => 'XYZ-789']);

        $response = $this->actingAs($admin)->getJson('/api/cars?search=ABC');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });
});

describe('Filtering - User Filtering', function () {
    test('users can be filtered by role', function () {
        $admin = User::factory()->asAdmin()->create();

        User::factory()->count(3)->asUser()->create();
        User::factory()->count(2)->asMechanic()->create();

        $response = $this->actingAs($admin)->getJson('/api/users?role=mechanic');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    test('users can be searched by name', function () {
        $admin = User::factory()->asAdmin()->create();

        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        User::factory()->create(['name' => 'John Johnson']);

        $response = $this->actingAs($admin)->getJson('/api/users?search=John');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    test('users can be searched by email', function () {
        $admin = User::factory()->asAdmin()->create();

        User::factory()->create(['email' => 'test@example.com']);
        User::factory()->create(['email' => 'other@example.com']);

        $response = $this->actingAs($admin)->getJson('/api/users?search=test@');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });
});

describe('Pagination', function () {
    test('results are paginated by default', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Ticket::factory()->count(20)->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    });

    test('per_page parameter controls results per page', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Ticket::factory()->count(20)->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    test('pagination works with filters', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Ticket::factory()->count(15)->open()->create();
        Ticket::factory()->count(10)->assigned()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets?status=open&per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    });
});
