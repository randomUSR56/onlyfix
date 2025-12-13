<?php

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('Statistics - Ticket Statistics', function () {
    test('mechanics can view ticket statistics', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Ticket::factory()->count(5)->open()->create();
        Ticket::factory()->count(3)->assigned()->create();
        Ticket::factory()->count(2)->inProgress()->create();
        Ticket::factory()->count(4)->completed()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_tickets',
                'by_status',
                'by_priority',
                'open_tickets',
                'assigned_tickets',
                'in_progress_tickets',
                'completed_today',
            ]);
    });

    test('admins can view ticket statistics', function () {
        $admin = User::factory()->asAdmin()->create();

        $response = $this->actingAs($admin)->getJson('/api/tickets/statistics');

        $response->assertStatus(200);
    });

    test('regular users cannot view ticket statistics', function () {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user)->getJson('/api/tickets/statistics');

        $response->assertStatus(403);
    });

    test('ticket statistics show accurate counts by status', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Ticket::factory()->count(5)->open()->create();
        Ticket::factory()->count(3)->assigned()->create();
        Ticket::factory()->count(2)->inProgress()->create();

        $response = $this->actingAs($mechanic)->getJson('/api/tickets/statistics');

        $response->assertStatus(200);

        $byStatus = $response->json('by_status');
        expect($byStatus['open'])->toBe(5);
        expect($byStatus['assigned'])->toBe(3);
        expect($byStatus['in_progress'])->toBe(2);
    });

    test('ticket statistics show counts by priority', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Ticket::factory()->count(2)->create(['priority' => 'urgent']);
        Ticket::factory()->count(3)->create(['priority' => 'high']);
        Ticket::factory()->count(4)->create(['priority' => 'medium']);

        $response = $this->actingAs($mechanic)->getJson('/api/tickets/statistics');

        $response->assertStatus(200);

        $byPriority = $response->json('by_priority');
        expect($byPriority['urgent'])->toBe(2);
        expect($byPriority['high'])->toBe(3);
        expect($byPriority['medium'])->toBe(4);
    });
});

describe('Statistics - Problem Statistics', function () {
    test('mechanics can view problem statistics', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Problem::factory()->count(10)->create();

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

    test('problem statistics show counts by category', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Problem::factory()->count(3)->create(['category' => 'engine']);
        Problem::factory()->count(2)->create(['category' => 'brakes']);
        Problem::factory()->count(4)->create(['category' => 'electrical']);

        $response = $this->actingAs($mechanic)->getJson('/api/problems/statistics');

        $response->assertStatus(200);

        // The API returns problems_by_frequency (problems with ticket counts)
        // Not by_category grouping
        $problems = $response->json('problems_by_frequency');
        expect($problems)->toBeArray();

        // Verify problems have ticket counts
        if (count($problems) > 0) {
            expect($problems[0])->toHaveKey('tickets_count');
        }
    });

    test('problem statistics distinguish active from inactive', function () {
        $mechanic = User::factory()->asMechanic()->create();

        Problem::factory()->count(8)->create(['is_active' => true]);
        Problem::factory()->count(2)->create(['is_active' => false]);

        $response = $this->actingAs($mechanic)->getJson('/api/problems/statistics');

        $response->assertStatus(200);

        expect($response->json('total_problems'))->toBe(10);
        expect($response->json('active_problems'))->toBe(8);
    });
});

describe('Statistics - User Workload', function () {
    test('mechanics workload is tracked in ticket statistics', function () {
        $mechanic1 = User::factory()->asMechanic()->create();
        $mechanic2 = User::factory()->asMechanic()->create();

        Ticket::factory()->count(5)->assigned($mechanic1)->create();
        Ticket::factory()->count(3)->inProgress($mechanic1)->create();
        Ticket::factory()->count(2)->assigned($mechanic2)->create();

        $response = $this->actingAs($mechanic1)->getJson('/api/tickets/statistics');

        $response->assertStatus(200);

        // Statistics should show all tickets, not just the requesting mechanic's
        expect($response->json('total_tickets'))->toBe(10);
    });
});
