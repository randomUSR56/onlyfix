<?php

use App\Models\User;
use App\Models\Car;
use App\Models\Ticket;
use App\Models\Problem;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Web Controllers - Cars', function () {
    test('index returns inertia response for authenticated user', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('cars.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Index'));
    });

    test('create returns inertia response', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('cars.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Create'));
    });

    test('store creates car and redirects', function () {
        $user = User::factory()->asUser()->create();

        $carData = [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'license_plate' => 'ABC123',
            'vin' => '1HGBH41JXMN109186',
            'color' => 'Blue',
        ];

        $response = $this->actingAs($user)->post(route('cars.store'), $carData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('cars', [
            'license_plate' => 'ABC123',
            'user_id' => $user->id,
        ]);
    });

    test('show returns inertia response', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('cars.show', $car));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Show'));
    });

    test('edit returns inertia response', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('cars.edit', $car));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Edit'));
    });

    test('update modifies car and redirects', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();

        $response = $this->actingAs($user)->patch(route('cars.update', $car), [
            'make' => 'Honda',
            'model' => 'Accord',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'make' => 'Honda',
            'model' => 'Accord',
        ]);
    });

    test('destroy deletes car and redirects', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('cars.destroy', $car));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
    });

    test('users cannot view other users cars', function () {
        $user1 = User::factory()->asUser()->create();
        $user2 = User::factory()->asUser()->create();
        $car = Car::factory()->for($user2)->create();

        $response = $this->actingAs($user1)->get(route('cars.show', $car));

        $response->assertStatus(403);
    });

    test('mechanics can view all cars', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();

        $response = $this->actingAs($mechanic)->get(route('cars.show', $car));

        $response->assertStatus(200);
    });

    test('admins can create cars for other users', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();

        $carData = [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'license_plate' => 'XYZ789',
            'user_id' => $user->id,
        ];

        $response = $this->actingAs($admin)->post(route('cars.store'), $carData);

        $response->assertRedirect();
        $this->assertDatabaseHas('cars', [
            'license_plate' => 'XYZ789',
            'user_id' => $user->id,
        ]);
    });

    test('tickets returns inertia response with car tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()->for($user)->for($car)->create();
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($user)->get(route('cars.tickets', $car));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Tickets'));
    });
});

describe('Web Controllers - Tickets', function () {
    test('index returns inertia response', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('tickets.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Index'));
    });

    test('create returns inertia response with cars and problems', function () {
        $user = User::factory()->asUser()->create();
        Car::factory()->for($user)->create();
        Problem::factory()->create();

        $response = $this->actingAs($user)->get(route('tickets.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Create'));
    });

    test('store creates ticket and redirects', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();

        $ticketData = [
            'car_id' => $car->id,
            'description' => 'Engine making strange noise',
            'priority' => 'high',
            'problem_ids' => [$problem->id],
        ];

        $response = $this->actingAs($user)->post(route('tickets.store'), $ticketData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tickets', [
            'car_id' => $car->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
    });

    test('show returns inertia response', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()->for($user)->for($car)->create();
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($user)->get(route('tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Show'));
    });

    test('edit returns inertia response', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()->for($user)->for($car)->create();
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($user)->get(route('tickets.edit', $ticket));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Edit'));
    });

    test('update modifies ticket and redirects', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()->for($user)->for($car)->create();
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($user)->patch(route('tickets.update', $ticket), [
            'description' => 'Updated description',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'description' => 'Updated description',
        ]);
    });

    test('destroy deletes ticket and redirects', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()->for($user)->for($car)->create(['status' => 'open']);
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($user)->delete(route('tickets.destroy', $ticket));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    });

    test('accept assigns ticket to mechanic', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()->for($user)->for($car)->create(['status' => 'open']);
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($mechanic)->post(route('tickets.accept', $ticket));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'mechanic_id' => $mechanic->id,
            'status' => 'assigned',
        ]);
    });

    test('start changes ticket status to in_progress', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()
            ->for($user)
            ->for($car)
            ->for($mechanic, 'mechanic')
            ->create(['status' => 'assigned']);
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($mechanic)->post(route('tickets.start', $ticket));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);
    });

    test('complete marks ticket as completed', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()
            ->for($user)
            ->for($car)
            ->for($mechanic, 'mechanic')
            ->create(['status' => 'in_progress']);
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($mechanic)->post(route('tickets.complete', $ticket));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'completed',
        ]);
    });

    test('close marks ticket as closed', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->for($user)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()
            ->for($user)
            ->for($car)
            ->create(['status' => 'completed']);
        $ticket->problems()->attach($problem->id);

        $response = $this->actingAs($user)->post(route('tickets.close', $ticket));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    });

    test('users cannot create tickets for other users cars', function () {
        $user1 = User::factory()->asUser()->create();
        $user2 = User::factory()->asUser()->create();
        $car = Car::factory()->for($user2)->create();
        $problem = Problem::factory()->create();

        $ticketData = [
            'car_id' => $car->id,
            'description' => 'Test ticket',
            'problem_ids' => [$problem->id],
        ];

        $response = $this->actingAs($user1)->post(route('tickets.store'), $ticketData);

        $response->assertSessionHasErrors();
    });
});

describe('Web Controllers - Problems', function () {
    test('index returns inertia response', function () {
        $user = User::factory()->asUser()->create();
        Problem::factory()->create();

        $response = $this->actingAs($user)->get(route('problems.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Problems/Index'));
    });

    test('show returns inertia response', function () {
        $user = User::factory()->asUser()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($user)->get(route('problems.show', $problem));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Problems/Show'));
    });

    test('create requires mechanic or admin role', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('problems.create'));

        $response->assertStatus(403);
    });

    test('mechanic can create problem', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('problems.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Problems/Create'));
    });

    test('store creates problem and redirects', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $problemData = [
            'name' => 'Oil Leak',
            'category' => 'engine',
            'description' => 'Engine oil leaking from gasket',
            'is_active' => true,
        ];

        $response = $this->actingAs($mechanic)->post(route('problems.store'), $problemData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('problems', [
            'name' => 'Oil Leak',
            'category' => 'engine',
        ]);
    });

    test('edit requires mechanic or admin role', function () {
        $user = User::factory()->asUser()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($user)->get(route('problems.edit', $problem));

        $response->assertStatus(403);
    });

    test('update modifies problem and redirects', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($mechanic)->patch(route('problems.update', $problem), [
            'description' => 'Updated description',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('problems', [
            'id' => $problem->id,
            'description' => 'Updated description',
        ]);
    });

    test('destroy requires admin role', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($mechanic)->delete(route('problems.destroy', $problem));

        $response->assertStatus(403);
    });

    test('admin can delete problem', function () {
        $admin = User::factory()->asAdmin()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($admin)->delete(route('problems.destroy', $problem));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('problems', ['id' => $problem->id]);
    });

    test('statistics returns inertia response for mechanics', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Problem::factory()->count(3)->create();

        $response = $this->actingAs($mechanic)->get(route('statistics.problems'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Statistics/Problems'));
    });

    test('statistics requires mechanic or admin role', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('statistics.problems'));

        $response->assertStatus(403);
    });
});

describe('Web Controllers - Users', function () {
    test('index requires admin role', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertStatus(403);
    });

    test('admin can view users index', function () {
        $admin = User::factory()->asAdmin()->create();
        User::factory()->count(3)->asUser()->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Users/Index'));
    });

    test('create requires admin role', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('users.create'));

        $response->assertStatus(403);
    });

    test('admin can create user', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Users/Create'));
    });

    test('store creates user and redirects', function () {
        $admin = User::factory()->asAdmin()->create();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'user',
        ];

        $response = $this->actingAs($admin)->post(route('users.store'), $userData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    test('users can view their own profile', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('users.show', $user));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Users/Show'));
    });

    test('users cannot view other users profiles', function () {
        $user1 = User::factory()->asUser()->create();
        $user2 = User::factory()->asUser()->create();

        $response = $this->actingAs($user1)->get(route('users.show', $user2));

        $response->assertStatus(403);
    });

    test('admin can view any user profile', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertStatus(200);
    });

    test('users can edit their own profile', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('users.edit', $user));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Users/Edit'));
    });

    test('update modifies user and redirects', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->patch(route('users.update', $user), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    });

    test('users cannot change their own role', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->patch(route('users.update', $user), [
            'role' => 'admin',
        ]);

        $response->assertSessionHasErrors();
    });

    test('admin can change user roles', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($admin)->patch(route('users.update', $user), [
            'role' => 'mechanic',
        ]);

        $response->assertRedirect();
        expect($user->fresh()->hasRole('mechanic'))->toBeTrue();
    });

    test('admin cannot delete themselves', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    });

    test('admin can delete other users', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $user));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    test('mechanics list requires mechanic or admin role', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('mechanics.index'));

        $response->assertStatus(403);
    });

    test('mechanics can view mechanics list', function () {
        $mechanic = User::factory()->asMechanic()->create();
        User::factory()->count(2)->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('mechanics.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Mechanics/Index'));
    });
});

describe('Web Controllers - Statistics', function () {
    test('ticket statistics requires mechanic or admin role', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('statistics.tickets'));

        $response->assertStatus(403);
    });

    test('mechanics can view ticket statistics', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('statistics.tickets'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Statistics/Tickets'));
    });

    test('admins can view ticket statistics', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->get(route('statistics.tickets'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Statistics/Tickets'));
    });
});
