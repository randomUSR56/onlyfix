<?php

use App\Models\Problem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Problem API - Authentication', function () {
    test('unauthenticated users cannot access problems endpoint', function () {
        $response = $this->getJson('/api/problems');
        $response->assertStatus(401);
    });
});

describe('Problem API - Index', function () {
    test('authenticated users can view problems', function () {
        $user = User::factory()->asUser()->create();
        Problem::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson('/api/problems');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

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

        Problem::factory()->count(3)->create(['is_active' => true]);
        Problem::factory()->count(2)->inactive()->create();

        $response = $this->actingAs($user)->getJson('/api/problems?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('problems can be searched', function () {
        $user = User::factory()->asUser()->create();

        Problem::factory()->create(['name' => 'Oil leak']);
        Problem::factory()->create(['name' => 'Brake pad wear']);

        $response = $this->actingAs($user)->getJson('/api/problems?search=oil');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });
});

describe('Problem API - Store', function () {
    test('mechanics can create problems', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $problemData = [
            'name' => 'Transmission slipping',
            'category' => 'transmission',
            'description' => 'Gears slip under load',
            'is_active' => true,
        ];

        $response = $this->actingAs($mechanic)->postJson('/api/problems', $problemData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Transmission slipping');

        $this->assertDatabaseHas('problems', [
            'name' => 'Transmission slipping',
        ]);
    });

    test('admins can create problems', function () {
        $admin = User::factory()->asAdmin()->create();

        $problemData = [
            'name' => 'Check engine light',
            'category' => 'engine',
        ];

        $response = $this->actingAs($admin)->postJson('/api/problems', $problemData);

        $response->assertStatus(201);
    });

    test('regular users cannot create problems', function () {
        $user = User::factory()->asUser()->create();

        $problemData = [
            'name' => 'Brake noise',
            'category' => 'Brakes',
        ];

        $response = $this->actingAs($user)->postJson('/api/problems', $problemData);

        $response->assertStatus(403);
    });

    test('problem creation validates required fields', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->postJson('/api/problems', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category']);
    });

    test('problem name must be unique', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Problem::factory()->create(['name' => 'Oil leak']);

        $problemData = [
            'name' => 'Oil leak',
            'category' => 'Engine',
        ];

        $response = $this->actingAs($mechanic)->postJson('/api/problems', $problemData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });
});

describe('Problem API - Show', function () {
    test('any authenticated user can view a problem', function () {
        $user = User::factory()->asUser()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/problems/{$problem->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $problem->id);
    });
});

describe('Problem API - Update', function () {
    test('mechanics can update problems', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($mechanic)->putJson("/api/problems/{$problem->id}", [
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.description', 'Updated description');
    });

    test('admins can update problems', function () {
        $admin = User::factory()->asAdmin()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/problems/{$problem->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);
    });

    test('regular users cannot update problems', function () {
        $user = User::factory()->asUser()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($user)->putJson("/api/problems/{$problem->id}", [
            'description' => 'New description',
        ]);

        $response->assertStatus(403);
    });
});

describe('Problem API - Delete', function () {
    test('admins can delete problems', function () {
        $admin = User::factory()->asAdmin()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/problems/{$problem->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('problems', ['id' => $problem->id]);
    });

    test('mechanics cannot delete problems', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($mechanic)->deleteJson("/api/problems/{$problem->id}");

        $response->assertStatus(403);
    });

    test('regular users cannot delete problems', function () {
        $user = User::factory()->asUser()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/problems/{$problem->id}");

        $response->assertStatus(403);
    });
});

describe('Problem API - Statistics', function () {
    test('mechanics can view problem statistics', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Problem::factory()->count(5)->create();

        $response = $this->actingAs($mechanic)->getJson('/api/problems/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_problems',
                'active_problems',
                'problems_by_frequency',
            ]);
    });

    test('admins can view problem statistics', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->getJson('/api/problems/statistics');

        $response->assertStatus(200);
    });

    test('regular users cannot view problem statistics', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson('/api/problems/statistics');

        $response->assertStatus(403);
    });
});
