<?php

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Ticket Workflow - Accept', function () {
    test('mechanics can accept open tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->open()->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        $response->assertStatus(200);

        $ticket->refresh();
        expect($ticket->status)->toBe('assigned');
        expect($ticket->mechanic_id)->toBe($mechanic->id);
        expect($ticket->accepted_at)->not->toBeNull();
    });

    test('admins can accept open tickets', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticket = Ticket::factory()->open()->create();

        $response = $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/accept");

        $response->assertStatus(200);
        expect($ticket->fresh()->status)->toBe('assigned');
    });

    test('regular users cannot accept tickets', function () {
        $user = User::factory()->asUser()->create();
        $ticket = Ticket::factory()->open()->create();

        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/accept");

        $response->assertStatus(403);
    });

    test('cannot accept already assigned ticket', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->assigned()->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        $response->assertStatus(422);
    });

    test('cannot accept completed ticket', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->completed()->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        $response->assertStatus(422);
    });
});

describe('Ticket Workflow - Start Work', function () {
    test('mechanics can start work on assigned tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->assigned($mechanic)->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/start");

        $response->assertStatus(200);

        $ticket->refresh();
        expect($ticket->status)->toBe('in_progress');
    });

    test('mechanics can only start their own assigned tickets', function () {
        $mechanic1 = User::factory()->asMechanic()->create();
        $mechanic2 = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->assigned($mechanic1)->create();

        $response = $this->actingAs($mechanic2)->postJson("/api/tickets/{$ticket->id}/start");

        $response->assertStatus(403);
    });

    test('cannot start work on open ticket not assigned to mechanic', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->open()->create();

        // Authorization check happens before status check
        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/start");

        $response->assertStatus(403);
    });

    test('admins can start work on any assigned ticket', function () {
        $admin = User::factory()->asAdmin()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->assigned($mechanic)->create();

        $response = $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/start");

        $response->assertStatus(200);
    });
});

describe('Ticket Workflow - Complete', function () {
    test('mechanics can complete their in-progress tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->inProgress($mechanic)->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/complete");

        $response->assertStatus(200);

        $ticket->refresh();
        expect($ticket->status)->toBe('completed');
        expect($ticket->completed_at)->not->toBeNull();
    });

    test('mechanics cannot complete other mechanics tickets', function () {
        $mechanic1 = User::factory()->asMechanic()->create();
        $mechanic2 = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->inProgress($mechanic1)->create();

        $response = $this->actingAs($mechanic2)->postJson("/api/tickets/{$ticket->id}/complete");

        $response->assertStatus(403);
    });

    test('cannot complete ticket not in progress', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->open()->create();

        // Authorization check happens before status check
        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/complete");

        $response->assertStatus(403);
    });

    test('admins can complete any in-progress ticket', function () {
        $admin = User::factory()->asAdmin()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->inProgress($mechanic)->create();

        $response = $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/complete");

        $response->assertStatus(200);
    });
});

describe('Ticket Workflow - Close', function () {
    test('ticket owners can close completed tickets', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->completed()->create();

        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/close");

        $response->assertStatus(200);

        $ticket->refresh();
        expect($ticket->status)->toBe('closed');
    });

    test('users cannot close other users tickets', function () {
        $user1 = User::factory()->asUser()->create();
        $user2 = User::factory()->asUser()->create();
        $ticket = Ticket::factory()->completed()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->postJson("/api/tickets/{$ticket->id}/close");

        $response->assertStatus(403);
    });

    test('cannot close already closed ticket', function () {
        $user = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($user)->create();
        $ticket = Ticket::factory()->forCar($car)->closed()->create();

        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/close");

        $response->assertStatus(422);
    });

    test('admins can close any completed ticket', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticket = Ticket::factory()->completed()->create();

        $response = $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/close");

        $response->assertStatus(200);
    });

    test('mechanics cannot close tickets', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->completed()->create();

        $response = $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/close");

        $response->assertStatus(403);
    });
});

describe('Ticket Workflow - Complete Workflow', function () {
    test('ticket can go through complete workflow', function () {
        $user = User::factory()->asUser()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->forUser($user)->create();
        $problems = Problem::factory()->count(2)->create();

        // Create ticket
        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'car_id' => $car->id,
            'description' => 'Engine making noise',
            'priority' => 'high',
            'problem_ids' => $problems->pluck('id')->toArray(),
        ]);

        $response->assertStatus(201);
        $ticketId = $response->json('data.id');

        // Check initial status
        $ticket = Ticket::find($ticketId);
        expect($ticket->status)->toBe('open');
        expect($ticket->mechanic_id)->toBeNull();

        // Mechanic accepts
        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticketId}/accept");
        $ticket->refresh();
        expect($ticket->status)->toBe('assigned');
        expect($ticket->mechanic_id)->toBe($mechanic->id);

        // Mechanic starts work
        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticketId}/start");
        $ticket->refresh();
        expect($ticket->status)->toBe('in_progress');

        // Mechanic completes
        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticketId}/complete");
        $ticket->refresh();
        expect($ticket->status)->toBe('completed');
        expect($ticket->completed_at)->not->toBeNull();

        // User closes
        $this->actingAs($user)->postJson("/api/tickets/{$ticketId}/close");
        $ticket->refresh();
        expect($ticket->status)->toBe('closed');
    });
});
