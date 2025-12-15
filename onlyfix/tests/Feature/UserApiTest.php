<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('User API - Authentication', function () {
    test('unauthenticated users cannot access users endpoint', function () {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    });
});

describe('User API - Index', function () {
    test('admins can view all users', function () {
        $admin = User::factory()->asAdmin()->create();
        User::factory()->count(5)->create();

        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(6, 'data'); // 5 + admin
    });

    test('regular users cannot view all users', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson('/api/users');

        $response->assertStatus(403);
    });

    test('users can be filtered by role', function () {
        $admin = User::factory()->asAdmin()->create();
        User::factory()->count(3)->asUser()->create();
        User::factory()->count(2)->asMechanic()->create();

        $response = $this->actingAs($admin)->getJson('/api/users?role=mechanic');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    test('users can be searched', function () {
        $admin = User::factory()->asAdmin()->create();
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->actingAs($admin)->getJson('/api/users?search=john');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });
});

describe('User API - Store', function () {
    test('admins can create users', function () {
        $admin = User::factory()->asAdmin()->create();

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'mechanic',
        ];

        $response = $this->actingAs($admin)->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New User')
            ->assertJsonPath('data.email', 'newuser@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);

        $newUser = User::where('email', 'newuser@example.com')->first();
        expect($newUser->hasRole('mechanic'))->toBeTrue();
    });

    test('regular users cannot create users', function () {
        $user = User::factory()->asUser()->create();

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'user',
        ];

        $response = $this->actingAs($user)->postJson('/api/users', $userData);

        $response->assertStatus(403);
    });

    test('user creation validates required fields', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    });

    test('email must be unique', function () {
        $admin = User::factory()->asAdmin()->create();
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'role' => 'user',
        ];

        $response = $this->actingAs($admin)->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});

describe('User API - Show', function () {
    test('users can view their own profile', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $user->id);
    });

    test('users cannot view other users profiles', function () {
        $user = User::factory()->asUser()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/users/{$otherUser->id}");

        $response->assertStatus(403);
    });

    test('admins can view any user profile', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->getJson("/api/users/{$user->id}");

        $response->assertStatus(200);
    });
});

describe('User API - Update', function () {
    test('users can update their own profile', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->putJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    });

    test('users cannot update other users profiles', function () {
        $user = User::factory()->asUser()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->putJson("/api/users/{$otherUser->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    });

    test('admins can update any user', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/users/{$user->id}", [
            'name' => 'Admin Updated',
        ]);

        $response->assertStatus(200);
    });

    test('users cannot change their own role', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->putJson("/api/users/{$user->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(403);
    });

    test('admins can change user roles', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($admin)->putJson("/api/users/{$user->id}", [
            'role' => 'mechanic',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        expect($user->hasRole('mechanic'))->toBeTrue();
    });

    test('password is hashed when updated', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->putJson("/api/users/{$user->id}", [
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });
});

describe('User API - Delete', function () {
    test('admins can delete users', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    test('admins cannot delete themselves', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/users/{$admin->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    });

    test('regular users cannot delete users', function () {
        $user = User::factory()->asUser()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/users/{$otherUser->id}");

        $response->assertStatus(403);
    });
});

describe('User API - Me', function () {
    test('authenticated users can get their own profile', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson('/api/users/me');

        $response->assertStatus(200)
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    });
});

describe('User API - Mechanics', function () {
    test('mechanics can view mechanics list', function () {
        $mechanic = User::factory()->asMechanic()->create();
        User::factory()->count(3)->asMechanic()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/users/mechanics');

        $response->assertStatus(200)
            ->assertJsonCount(4);
    });

    test('admins can view mechanics list', function () {
        $admin = User::factory()->asAdmin()->create();
        User::factory()->count(2)->asMechanic()->create();

        $response = $this->actingAs($admin)->getJson('/api/users/mechanics');

        $response->assertStatus(200);
    });

    test('regular users cannot view mechanics list', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson('/api/users/mechanics');

        $response->assertStatus(403);
    });
});

describe('User API - User Tickets', function () {
    test('users can view their own tickets', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson("/api/users/{$user->id}/tickets");

        $response->assertStatus(200);
    });

    test('mechanics can view any users tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($mechanic)->getJson("/api/users/{$user->id}/tickets");

        $response->assertStatus(200);
    });

    test('users cannot view other users tickets', function () {
        $user = User::factory()->asUser()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/users/{$otherUser->id}/tickets");

        $response->assertStatus(403);
    });
});

describe('User API - User Cars', function () {
    test('users can view their own cars', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson("/api/users/{$user->id}/cars");

        $response->assertStatus(200);
    });

    test('mechanics can view any users cars', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($mechanic)->getJson("/api/users/{$user->id}/cars");

        $response->assertStatus(200);
    });

    test('users cannot view other users cars', function () {
        $user = User::factory()->asUser()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/users/{$otherUser->id}/cars");

        $response->assertStatus(403);
    });
});
