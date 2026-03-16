<?php

use App\Models\Ticket;

describe('Ticket Model - Status Helpers', function () {
    test('isOpen returns true for open tickets', function () {
        $ticket = new Ticket(['status' => 'open']);
        expect($ticket->isOpen())->toBeTrue();
        expect($ticket->isCompleted())->toBeFalse();
        expect($ticket->isClosed())->toBeFalse();
        expect($ticket->isInProgress())->toBeFalse();
    });

    test('isCompleted returns true for completed tickets', function () {
        $ticket = new Ticket(['status' => 'completed']);
        expect($ticket->isCompleted())->toBeTrue();
        expect($ticket->isOpen())->toBeFalse();
    });

    test('isClosed returns true for closed tickets', function () {
        $ticket = new Ticket(['status' => 'closed']);
        expect($ticket->isClosed())->toBeTrue();
        expect($ticket->isOpen())->toBeFalse();
    });

    test('isInProgress returns true for in_progress tickets', function () {
        $ticket = new Ticket(['status' => 'in_progress']);
        expect($ticket->isInProgress())->toBeTrue();
        expect($ticket->isOpen())->toBeFalse();
    });

    test('isAssigned returns true when mechanic_id is set', function () {
        $ticket = new Ticket(['mechanic_id' => 1]);
        expect($ticket->isAssigned())->toBeTrue();
    });

    test('isAssigned returns false when mechanic_id is null', function () {
        $ticket = new Ticket(['mechanic_id' => null]);
        expect($ticket->isAssigned())->toBeFalse();
    });
});

describe('Ticket Model - Casts', function () {
    test('accepted_at is cast to datetime', function () {
        $casts = (new Ticket())->getCasts();
        expect($casts['accepted_at'])->toBe('datetime');
    });

    test('completed_at is cast to datetime', function () {
        $casts = (new Ticket())->getCasts();
        expect($casts['completed_at'])->toBe('datetime');
    });
});

describe('Ticket Model - Fillable', function () {
    test('expected fields are fillable', function () {
        $fillable = (new Ticket())->getFillable();
        expect($fillable)->toContain('user_id')
            ->toContain('mechanic_id')
            ->toContain('car_id')
            ->toContain('status')
            ->toContain('priority')
            ->toContain('description')
            ->toContain('accepted_at')
            ->toContain('completed_at');
    });
});
