<?php

use App\Notifications\TicketStatusChanged;
use App\Models\Ticket;
use App\Models\User;

describe('TicketStatusChanged Notification', function () {
    test('notification stores ticket and status data', function () {
        $ticket = new Ticket([
            'id' => 1,
            'status' => 'assigned',
            'description' => 'Test ticket',
        ]);

        $notification = new TicketStatusChanged($ticket, 'open', 'assigned');

        expect($notification->ticket)->toBe($ticket);
        expect($notification->oldStatus)->toBe('open');
        expect($notification->newStatus)->toBe('assigned');
    });

    test('notification delivers via mail channel', function () {
        $ticket = new Ticket([
            'id' => 1,
            'status' => 'assigned',
            'description' => 'Test ticket',
        ]);

        $notification = new TicketStatusChanged($ticket, 'open', 'assigned');
        $channels = $notification->via(new User());

        expect($channels)->toBe(['mail']);
    });

    test('toArray returns expected structure', function () {
        $ticket = new Ticket(['id' => 42, 'status' => 'completed', 'description' => 'Test']);
        $ticket->id = 42;

        $notification = new TicketStatusChanged($ticket, 'in_progress', 'completed');
        $array = $notification->toArray(new User());

        expect($array)->toHaveKey('ticket_id', 42)
            ->toHaveKey('old_status', 'in_progress')
            ->toHaveKey('new_status', 'completed');
    });
});
