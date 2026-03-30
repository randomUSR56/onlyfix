<?php

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Validation - Car Validation', function () {
    test('car creation requires make', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->postJson('/api/cars', [
            'model' => 'Camry',
            'year' => 2020,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['make']);
    });

    test('car creation requires model', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->postJson('/api/cars', [
            'make' => 'Toyota',
            'year' => 2020,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['model']);
    });

    test('car creation requires valid year', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->postJson('/api/cars', [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 1899, // Too old
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year']);
    });

    test('car license plate must be unique', function () {
        $user = User::factory()->asUser()->create();
        Car::factory()->create(['license_plate' => 'ABC-123']);

        $response = $this->actingAs($user)->postJson('/api/cars', [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'license_plate' => 'ABC-123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['license_plate']);
    });

    test('car VIN must be unique if provided', function () {
        $user = User::factory()->asUser()->create();
        Car::factory()->create(['vin' => 'VIN123456789']);

        $response = $this->actingAs($user)->postJson('/api/cars', [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'vin' => 'VIN123456789',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vin']);
    });
});

describe('Validation - Ticket Validation', function () {
    test('ticket creation requires car_id', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'description' => 'Test',
            'priority' => 'high',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id']);
    });

    test('ticket creation requires valid car_id', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'car_id' => 99999,
            'description' => 'Test',
            'priority' => 'high',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id']);
    });

    test('ticket creation requires description', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'car_id' => $car->id,
            'priority' => 'high',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    });

    test('ticket creation requires valid priority', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'car_id' => $car->id,
            'description' => 'Test',
            'priority' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    });

    test('ticket problem_ids must be valid', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'car_id' => $car->id,
            'description' => 'Test',
            'priority' => 'high',
            'problem_ids' => [99999],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['problem_ids.0']);
    });
});

describe('Validation - Problem Validation', function () {
    test('problem creation requires name', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->postJson('/api/problems', [
            'category' => 'engine',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('problem creation requires category', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->postJson('/api/problems', [
            'name' => 'Oil leak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    });

    test('problem creation requires valid category', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->postJson('/api/problems', [
            'name' => 'Oil leak',
            'category' => 'invalid-category',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    });

    test('problem name must be unique', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Problem::factory()->create(['name' => 'Oil leak']);

        $response = $this->actingAs($mechanic)->postJson('/api/problems', [
            'name' => 'Oil leak',
            'category' => 'engine',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });
});

describe('Validation - User Validation', function () {
    test('user creation requires name', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('user creation requires valid email', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('user email must be unique', function () {
        $admin = User::factory()->asAdmin()->create();
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('user update validates email uniqueness', function () {
        $admin = User::factory()->asAdmin()->create();
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $response = $this->actingAs($admin)->putJson("/api/users/{$user1->id}", [
            'name' => 'Updated Name',
            'email' => 'user2@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});
