<?php

use App\Models\Car;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Car API - Authentication', function () {
    test('unauthenticated users cannot access cars endpoint', function () {
        $response = $this->getJson('/api/cars');
        $response->assertStatus(401);
    });
});

describe('Car API - Index', function () {
    test('users can view their own cars', function () {
        $user = User::factory()->asUser()->create();
        $cars = Car::factory()->count(3)->forUser($user)->create();
        $otherCars = Car::factory()->count(2)->create();

        $response = $this->actingAs($user)->getJson('/api/cars');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('mechanics can view all cars', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Car::factory()->count(5)->create();

        $response = $this->actingAs($mechanic)->getJson('/api/cars');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    test('admins can view all cars', function () {
        $admin = User::factory()->asAdmin()->create();
        Car::factory()->count(5)->create();

        $response = $this->actingAs($admin)->getJson('/api/cars');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    test('cars can be filtered by user_id', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->create();

        Car::factory()->count(3)->forUser($user)->create();
        Car::factory()->count(2)->create();

        $response = $this->actingAs($admin)->getJson('/api/cars?user_id=' . $user->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });
});

describe('Car API - Store', function () {
    test('users can create cars for themselves', function () {
        $user = User::factory()->asUser()->create();

        $carData = [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'license_plate' => 'ABC-1234',
            'vin' => 'VIN1234567890',
            'color' => 'Blue',
        ];

        $response = $this->actingAs($user)->postJson('/api/cars', $carData);

        $response->assertStatus(201)
            ->assertJsonPath('data.make', 'Toyota')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('cars', [
            'make' => 'Toyota',
            'user_id' => $user->id,
        ]);
    });

    test('admins can create cars for other users', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->create();

        $carData = [
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2021,
            'license_plate' => 'XYZ-5678',
            'user_id' => $user->id,
        ];

        $response = $this->actingAs($admin)->postJson('/api/cars', $carData);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $user->id);
    });

    test('regular users cannot create cars for other users', function () {
        $user = User::factory()->asUser()->create();
        $otherUser = User::factory()->create();

        $carData = [
            'make' => 'Ford',
            'model' => 'Focus',
            'year' => 2019,
            'license_plate' => 'DEF-9012',
            'user_id' => $otherUser->id,
        ];

        $response = $this->actingAs($user)->postJson('/api/cars', $carData);

        $response->assertStatus(403);
    });

    test('car creation validates required fields', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->postJson('/api/cars', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['make', 'model', 'year', 'license_plate']);
    });

    test('license plate must be unique', function () {
        $user = User::factory()->asUser()->create();
        Car::factory()->create(['license_plate' => 'ABC-1234']);

        $carData = [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'license_plate' => 'ABC-1234',
        ];

        $response = $this->actingAs($user)->postJson('/api/cars', $carData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['license_plate']);
    });
});

describe('Car API - Show', function () {
    test('users can view their own cars', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->getJson("/api/cars/{$car->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $car->id);
    });

    test('users cannot view other users cars', function () {
        $user = User::factory()->asUser()->create();
        $otherCar = Car::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/cars/{$otherCar->id}");

        $response->assertStatus(403);
    });

    test('mechanics can view any car', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->create();

        $response = $this->actingAs($mechanic)->getJson("/api/cars/{$car->id}");

        $response->assertStatus(200);
    });
});

describe('Car API - Update', function () {
    test('users can update their own cars', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->putJson("/api/cars/{$car->id}", [
            'color' => 'Red',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.color', 'Red');

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'color' => 'Red',
        ]);
    });

    test('users cannot update other users cars', function () {
        $user = User::factory()->asUser()->create();
        $otherCar = Car::factory()->create();

        $response = $this->actingAs($user)->putJson("/api/cars/{$otherCar->id}", [
            'color' => 'Red',
        ]);

        $response->assertStatus(403);
    });

    test('admins can update any car', function () {
        $admin = User::factory()->asAdmin()->create();
        $car = Car::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/cars/{$car->id}", [
            'color' => 'Green',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.color', 'Green');
    });
});

describe('Car API - Delete', function () {
    test('users can delete their own cars', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->deleteJson("/api/cars/{$car->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
    });

    test('users cannot delete other users cars', function () {
        $user = User::factory()->asUser()->create();
        $otherCar = Car::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/cars/{$otherCar->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('cars', ['id' => $otherCar->id]);
    });

    test('admins can delete any car', function () {
        $admin = User::factory()->asAdmin()->create();
        $car = Car::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/cars/{$car->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
    });
});

describe('Car API - Tickets', function () {
    test('users can view tickets for their own cars', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->getJson("/api/cars/{$car->id}/tickets");

        $response->assertStatus(200);
    });

    test('users cannot view tickets for other users cars', function () {
        $user = User::factory()->asUser()->create();
        $otherCar = Car::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/cars/{$otherCar->id}/tickets");

        $response->assertStatus(403);
    });
});
