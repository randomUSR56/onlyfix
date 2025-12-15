<?php

use App\Models\User;
use App\Models\Car;
use App\Models\Ticket;
use App\Models\Problem;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Web Routes - Public', function () {
    test('welcome page is accessible', function () {
        $response = $this->get('/');

        $response->assertStatus(200);
    });

    test('dashboard requires authentication', function () {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    });
});

describe('Web Routes - Dashboard', function () {
    test('authenticated users can access dashboard', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    });
});

describe('Web Routes - Cars', function () {
    test('authenticated users can view cars index', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('cars.index'));

        $response->assertStatus(200);
    });

    test('authenticated users can view create car form', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('cars.create'));

        $response->assertStatus(200);
    });

    test('authenticated users can view their own car', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->get(route('cars.show', $car));

        $response->assertStatus(200);
    });

    test('users cannot view other users cars', function () {
        $user1 = User::factory()->asUser()->create();
        $user2 = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user2)->create();

        $response = $this->actingAs($user1)->get(route('cars.show', $car));

        $response->assertStatus(403);
    });

    test('mechanics can view all cars', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($mechanic)->get(route('cars.show', $car));

        $response->assertStatus(200);
    });

    test('authenticated users can view edit form for their own car', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->get(route('cars.edit', $car));

        $response->assertStatus(200);
    });

    test('authenticated users can view tickets for their car', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->get(route('cars.tickets', $car));

        $response->assertStatus(200);
    });
});

describe('Web Routes - Tickets', function () {
    test('authenticated users can view tickets index', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('tickets.index'));

        $response->assertStatus(200);
    });

    test('authenticated users can view create ticket form', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('tickets.create'));

        $response->assertStatus(200);
    });

    test('users can view their own tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->create();

        $response = $this->actingAs($user)->get(route('tickets.show', $ticket));

        $response->assertStatus(200);
    });

    test('users cannot view other users tickets', function () {
        $user1 = User::factory()->asUser()->create();
        $user2 = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user2)->create();
        $ticket = Ticket::factory()->forCar($car)->create();

        $response = $this->actingAs($user1)->get(route('tickets.show', $ticket));

        $response->assertStatus(403);
    });

    test('mechanics can view all tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->create();

        $response = $this->actingAs($mechanic)->get(route('tickets.show', $ticket));

        $response->assertStatus(200);
    });

    test('users can view edit form for their own open tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->open()->create();

        $response = $this->actingAs($user)->get(route('tickets.edit', $ticket));

        $response->assertStatus(200);
    });

    test('regular users cannot accept tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->open()->create();

        $response = $this->actingAs($user)->post(route('tickets.accept', $ticket));

        $response->assertStatus(403);
    });

    test('mechanics can access accept ticket route', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->open()->create();

        $response = $this->actingAs($mechanic)->post(route('tickets.accept', $ticket));

        $response->assertRedirect(); // Redirects after successful action
    });

    test('mechanics can access start work route', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->assigned($mechanic)->create();

        $response = $this->actingAs($mechanic)->post(route('tickets.start', $ticket));

        $response->assertRedirect();
    });

    test('mechanics can access complete ticket route', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->inProgress($mechanic)->create();

        $response = $this->actingAs($mechanic)->post(route('tickets.complete', $ticket));

        $response->assertRedirect();
    });

    test('users can access close ticket route for their tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->completed()->create();

        $response = $this->actingAs($user)->post(route('tickets.close', $ticket));

        $response->assertRedirect();
    });
});

describe('Web Routes - Problems', function () {
    test('authenticated users can view problems index', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('problems.index'));

        $response->assertStatus(200);
    });

    test('authenticated users can view a problem', function () {
        $user = User::factory()->asUser()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($user)->get(route('problems.show', $problem));

        $response->assertStatus(200);
    });

    test('regular users cannot create problems', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('problems.create'));

        $response->assertStatus(403);
    });

    test('mechanics can access create problem form', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('problems.create'));

        $response->assertStatus(200);
    });

    test('admins can access create problem form', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->get(route('problems.create'));

        $response->assertStatus(200);
    });

    test('mechanics can access edit problem form', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($mechanic)->get(route('problems.edit', $problem));

        $response->assertStatus(200);
    });

    test('regular users cannot delete problems', function () {
        $user = User::factory()->asUser()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($user)->delete(route('problems.destroy', $problem));

        $response->assertStatus(403);
    });

    test('mechanics cannot delete problems', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $problem = Problem::factory()->create();

        $response = $this->actingAs($mechanic)->delete(route('problems.destroy', $problem));

        $response->assertStatus(403);
    });
});

describe('Web Routes - Users', function () {
    test('regular users cannot access users index', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertStatus(403);
    });

    test('mechanics cannot access users index', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('users.index'));

        $response->assertStatus(403);
    });

    test('admins can access users index', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
    });

    test('admins can access create user form', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertStatus(200);
    });

    test('admins can view user details', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertStatus(200);
    });

    test('admins can access edit user form', function () {
        $admin = User::factory()->asAdmin()->create();
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($admin)->get(route('users.edit', $user));

        $response->assertStatus(200);
    });
});

describe('Web Routes - Statistics', function () {
    test('regular users cannot access ticket statistics', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('statistics.tickets'));

        $response->assertStatus(403);
    });

    test('mechanics can access ticket statistics', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('statistics.tickets'));

        $response->assertStatus(200);
    });

    test('admins can access ticket statistics', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->get(route('statistics.tickets'));

        $response->assertStatus(200);
    });

    test('mechanics can access problem statistics', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('statistics.problems'));

        $response->assertStatus(200);
    });

    test('regular users cannot access problem statistics', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('statistics.problems'));

        $response->assertStatus(403);
    });
});

describe('Web Routes - Mechanics', function () {
    test('regular users cannot access mechanics list', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->get(route('mechanics.index'));

        $response->assertStatus(403);
    });

    test('mechanics can access mechanics list', function () {
        $mechanic = User::factory()->asMechanic()->create();

        $response = $this->actingAs($mechanic)->get(route('mechanics.index'));

        $response->assertStatus(200);
    });

    test('admins can access mechanics list', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->get(route('mechanics.index'));

        $response->assertStatus(200);
    });
});

describe('Web Routes - Authentication Required', function () {
    test('unauthenticated users are redirected to login for cars', function () {
        $response = $this->get(route('cars.index'));

        $response->assertRedirect('/login');
    });

    test('unauthenticated users are redirected to login for tickets', function () {
        $response = $this->get(route('tickets.index'));

        $response->assertRedirect('/login');
    });

    test('unauthenticated users are redirected to login for problems', function () {
        $response = $this->get(route('problems.index'));

        $response->assertRedirect('/login');
    });

    test('unauthenticated users are redirected to login for statistics', function () {
        $response = $this->get(route('statistics.tickets'));

        $response->assertRedirect('/login');
    });
});
