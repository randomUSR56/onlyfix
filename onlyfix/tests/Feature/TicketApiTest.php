<?php

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Ticket API - Authentication', function () {
    test('unauthenticated users cannot access tickets endpoint', function () {
        $response = $this->getJson('/api/tickets');
        $response->assertStatus(401);
    });
});

describe('Ticket API - Index', function () {
    test('users can view their own tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        Ticket::factory()->count(3)->forCar($car)->create();
        Ticket::factory()->count(2)->create(); // Other users' tickets

        $response = $this->actingAs($user)->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('mechanics can view all tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Ticket::factory()->count(5)->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    test('tickets can be filtered by status', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Ticket::factory()->count(2)->open()->create();
        Ticket::factory()->count(3)->assigned()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets?status=open');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    test('tickets can be filtered by priority', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Ticket::factory()->count(2)->urgent()->create();
        Ticket::factory()->count(3)->low()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets?priority=urgent');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });
});

describe('Ticket API - Store', function () {
    test('users can create tickets for their own cars', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $problems = Problem::factory()->count(2)->create();

        $ticketData = [
            'car_id' => $car->id,
            'description' => 'My car is making strange noises',
            'priority' => 'high',
            'problem_ids' => $problems->pluck('id')->toArray(),
            'problem_notes' => ['Loud noise', 'Intermittent'],
        ];

        $response = $this->actingAs($user)->postJson('/api/tickets', $ticketData);

        $response->assertStatus(201)
            ->assertJsonPath('data.description', 'My car is making strange noises')
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
            'car_id' => $car->id,
            'status' => 'open',
        ]);
    });

    test('users cannot create tickets for other users cars', function () {
        $user = User::factory()->asUser()->create();
        $otherCar = Car::factory()->create();
        $problems = Problem::factory()->count(2)->create();

        $ticketData = [
            'car_id' => $otherCar->id,
            'description' => 'Test',
            'problem_ids' => $problems->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user)->postJson('/api/tickets', $ticketData);

        $response->assertStatus(403);
    });

    test('ticket creation validates required fields', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->postJson('/api/tickets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id', 'description', 'problem_ids']);
    });

    test('ticket requires at least one problem', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();

        $ticketData = [
            'car_id' => $car->id,
            'description' => 'Test',
            'problem_ids' => [],
        ];

        $response = $this->actingAs($user)->postJson('/api/tickets', $ticketData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['problem_ids']);
    });
});

describe('Ticket API - Show', function () {
    test('users can view their own tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->create();

        $response = $this->actingAs($user)->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $ticket->id);
    });

    test('users cannot view other users tickets', function () {
        $user = User::factory()->asUser()->create();
        $otherTicket = Ticket::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/tickets/{$otherTicket->id}");

        $response->assertStatus(403);
    });

    test('mechanics can view any ticket', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($mechanic)->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200);
    });
});

describe('Ticket API - Update', function () {
    test('users can update their own open tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->open()->create();

        $response = $this->actingAs($user)->putJson("/api/tickets/{$ticket->id}", [
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.description', 'Updated description');
    });

    test('users cannot update tickets that are not open', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->assigned()->create();

        $response = $this->actingAs($user)->putJson("/api/tickets/{$ticket->id}", [
            'description' => 'Updated',
        ]);

        $response->assertStatus(403);
    });

    test('mechanics can update any ticket', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->assigned()->create();

        $response = $this->actingAs($mechanic)->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200);
    });

    test('users cannot change ticket status', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->open()->create();

        $response = $this->actingAs($user)->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200);

        $ticket->refresh();
        expect($ticket->status)->toBe('open');
    });
});

describe('Ticket API - Delete', function () {
    test('users can delete their own open tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->open()->create();

        $response = $this->actingAs($user)->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    });

    test('users cannot delete tickets that are not open', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->assigned()->create();

        $response = $this->actingAs($user)->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(403);
    });

    test('admins can delete any ticket', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticket = Ticket::factory()->assigned()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    });
});

describe('Ticket API - Accept', function () {
    test('mechanics can accept open tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->open()->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.mechanic_id', $mechanic->id);

        $ticket->refresh();
        expect($ticket->mechanic_id)->toBe($mechanic->id);
        expect($ticket->accepted_at)->not->toBeNull();
    });

    test('regular users cannot accept tickets', function () {
        $user = User::factory()->asUser()->create();
        $ticket = Ticket::factory()->open()->create();

        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/accept");

        $response->assertStatus(403);
    });

    test('cannot accept already assigned tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->assigned()->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        $response->assertStatus(422);
    });
});

describe('Ticket API - Start Work', function () {
    test('mechanics can start work on their assigned tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->assigned($mechanic)->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/start");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');
    });

    test('mechanics cannot start work on tickets assigned to others', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $otherMechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->assigned($otherMechanic)->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/start");

        $response->assertStatus(403);
    });

    test('admins can start work on any ticket', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticket = Ticket::factory()->assigned()->create();

        $response = $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/start");

        $response->assertStatus(200);
    });
});

describe('Ticket API - Complete', function () {
    test('mechanics can complete their assigned tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->inProgress($mechanic)->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $ticket->refresh();
        expect($ticket->completed_at)->not->toBeNull();
    });

    test('mechanics cannot complete tickets assigned to others', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $otherMechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->inProgress($otherMechanic)->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/complete");

        $response->assertStatus(403);
    });
});

describe('Ticket API - Close', function () {
    test('users can close their own tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->completed()->create();

        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/close");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');
    });

    test('users cannot close other users tickets', function () {
        $user = User::factory()->asUser()->create();
        $ticket = Ticket::factory()->completed()->create();

        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/close");

        $response->assertStatus(403);
    });

    test('admins can close any ticket', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticket = Ticket::factory()->completed()->create();

        $response = $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/close");

        $response->assertStatus(200);
    });
});

describe('Ticket API - Statistics', function () {
    test('mechanics can view ticket statistics', function () {
        $mechanic = User::factory()->asMechanic()->create();
        Ticket::factory()->count(3)->open()->create();
        Ticket::factory()->count(2)->assigned()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_tickets',
                'by_status',
                'by_priority',
                'open_tickets',
            ]);
    });

    test('regular users cannot view ticket statistics', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson('/api/tickets/statistics');

        $response->assertStatus(403);
    });
});
