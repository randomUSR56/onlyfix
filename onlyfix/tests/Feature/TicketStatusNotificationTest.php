<?php

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusChanged;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();
});

describe('Ticket Status Notification - Accept', function () {
    test('accepting a ticket notifies the ticket owner and admins', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticketOwner = User::factory()->asUser()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->forUser($ticketOwner)->create();
        $ticket = Ticket::factory()->open()->forCar($car)->create();

        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        Notification::assertSentTo($ticketOwner, TicketStatusChanged::class, function ($notification) {
            return $notification->oldStatus === 'open'
                && $notification->newStatus === 'assigned';
        });

        Notification::assertSentTo($admin, TicketStatusChanged::class);
    });

    test('accepting a ticket does not notify the mechanic who accepted', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->open()->create();

        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        Notification::assertNotSentTo($mechanic, TicketStatusChanged::class);
    });
});

describe('Ticket Status Notification - Start Work', function () {
    test('starting work notifies the ticket owner and admins', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticketOwner = User::factory()->asUser()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->forUser($ticketOwner)->create();
        $ticket = Ticket::factory()->assigned($mechanic)->forCar($car)->create();

        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/start");

        Notification::assertSentTo($ticketOwner, TicketStatusChanged::class, function ($notification) {
            return $notification->oldStatus === 'assigned'
                && $notification->newStatus === 'in_progress';
        });

        Notification::assertSentTo($admin, TicketStatusChanged::class);
    });
});

describe('Ticket Status Notification - Complete', function () {
    test('completing a ticket notifies the ticket owner and admins', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticketOwner = User::factory()->asUser()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->forUser($ticketOwner)->create();
        $ticket = Ticket::factory()->inProgress($mechanic)->forCar($car)->create();

        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/complete");

        Notification::assertSentTo($ticketOwner, TicketStatusChanged::class, function ($notification) {
            return $notification->oldStatus === 'in_progress'
                && $notification->newStatus === 'completed';
        });

        Notification::assertSentTo($admin, TicketStatusChanged::class);
    });
});

describe('Ticket Status Notification - Close', function () {
    test('closing a ticket notifies the ticket owner and admins', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticketOwner = User::factory()->asUser()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->forUser($ticketOwner)->create();
        $ticket = Ticket::factory()->completed($mechanic)->forCar($car)->create();

        $this->actingAs($ticketOwner)->postJson("/api/tickets/{$ticket->id}/close");

        Notification::assertSentTo($ticketOwner, TicketStatusChanged::class, function ($notification) {
            return $notification->oldStatus === 'completed'
                && $notification->newStatus === 'closed';
        });

        Notification::assertSentTo($admin, TicketStatusChanged::class);
    });

    test('admin closing a ticket sends notifications', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticketOwner = User::factory()->asUser()->create();
        $car = Car::factory()->forUser($ticketOwner)->create();
        $ticket = Ticket::factory()->open()->forCar($car)->create();

        $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/close");

        Notification::assertSentTo($ticketOwner, TicketStatusChanged::class);
        Notification::assertSentTo($admin, TicketStatusChanged::class);
    });
});

describe('Ticket Status Notification - Update with Status Change', function () {
    test('updating ticket status via update endpoint notifies users', function () {
        $admin = User::factory()->asAdmin()->create();
        $ticketOwner = User::factory()->asUser()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->forUser($ticketOwner)->create();
        $problem = Problem::factory()->create();
        $ticket = Ticket::factory()->open()->forCar($car)->create();
        $ticket->problems()->attach($problem->id);

        $this->actingAs($mechanic)->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'assigned',
        ]);

        Notification::assertSentTo($ticketOwner, TicketStatusChanged::class, function ($notification) {
            return $notification->oldStatus === 'open'
                && $notification->newStatus === 'assigned';
        });

        Notification::assertSentTo($admin, TicketStatusChanged::class);
    });

    test('updating ticket without status change does not send notification', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->open()->create();

        $this->actingAs($mechanic)->putJson("/api/tickets/{$ticket->id}", [
            'description' => 'Updated description only',
        ]);

        Notification::assertNothingSent();
    });

    test('updating ticket with same status does not send notification', function () {
        $mechanic = User::factory()->asMechanic()->create();
        $ticket = Ticket::factory()->open()->create();

        $this->actingAs($mechanic)->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'open',
        ]);

        Notification::assertNothingSent();
    });
});

describe('Ticket Status Notification - Admin Deduplication', function () {
    test('admin who owns the ticket receives only one notification', function () {
        $admin = User::factory()->asAdmin()->create();
        $car = Car::factory()->forUser($admin)->create();
        $ticket = Ticket::factory()->open()->forCar($car)->create();
        $mechanic = User::factory()->asMechanic()->create();

        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        // Admin is both ticket owner and admin - should get exactly 1 notification
        Notification::assertSentTo($admin, TicketStatusChanged::class);
        Notification::assertCount(1);
    });

    test('multiple admins all receive notifications', function () {
        $admin1 = User::factory()->asAdmin()->create();
        $admin2 = User::factory()->asAdmin()->create();
        $ticketOwner = User::factory()->asUser()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->forUser($ticketOwner)->create();
        $ticket = Ticket::factory()->open()->forCar($car)->create();

        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        Notification::assertSentTo($ticketOwner, TicketStatusChanged::class);
        Notification::assertSentTo($admin1, TicketStatusChanged::class);
        Notification::assertSentTo($admin2, TicketStatusChanged::class);
        // ticket owner + 2 admins = 3
        Notification::assertCount(3);
    });
});

describe('Ticket Status Notification - Content', function () {
    test('notification contains correct ticket and status information', function () {
        $ticketOwner = User::factory()->asUser()->create();
        $mechanic = User::factory()->asMechanic()->create();
        $car = Car::factory()->forUser($ticketOwner)->create();
        $ticket = Ticket::factory()->open()->forCar($car)->create();

        $this->actingAs($mechanic)->postJson("/api/tickets/{$ticket->id}/accept");

        Notification::assertSentTo($ticketOwner, TicketStatusChanged::class, function ($notification) use ($ticket) {
            return $notification->ticket->id === $ticket->id
                && $notification->oldStatus === 'open'
                && $notification->newStatus === 'assigned';
        });
    });

    test('notification mail has correct subject and content', function () {
        $ticketOwner = User::factory()->asUser()->create();
        $ticket = Ticket::factory()->open()->forCar(Car::factory()->forUser($ticketOwner)->create())->create();

        $notification = new TicketStatusChanged($ticket, 'open', 'assigned');
        $mail = $notification->toMail($ticketOwner);

        expect($mail->subject)->toBe("Ticket #{$ticket->id} Status Updated");
        expect($mail->greeting)->toBe("Hello {$ticketOwner->name},");
        expect($mail->actionUrl)->toBe(url("/tickets/{$ticket->id}"));
    });

    test('notification toArray contains correct data', function () {
        $ticket = Ticket::factory()->open()->create();

        $notification = new TicketStatusChanged($ticket, 'open', 'closed');
        $array = $notification->toArray($ticket->user);

        expect($array)->toBe([
            'ticket_id' => $ticket->id,
            'old_status' => 'open',
            'new_status' => 'closed',
        ]);
    });
});
