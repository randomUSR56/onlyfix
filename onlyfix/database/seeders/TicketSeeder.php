<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $mechanics = User::role('mechanic')->get();
        $adminUsers = User::role('admin')->get();
        $regularUsers = User::role('user')->get();
        $cars = Car::all();
        $problems = Problem::where('is_active', true)->get();

        if ($cars->isEmpty() || $problems->isEmpty()) {
            $this->command->warn('Please seed cars and problems first.');
            return;
        }

        // Combine mechanics and admins as potential assignees
        $allMechanics = $mechanics->merge($adminUsers);

        // Create various tickets with different statuses
        $this->createOpenTickets($regularUsers, $cars, $problems);
        $this->createAssignedTickets($regularUsers, $cars, $problems, $allMechanics);
        $this->createInProgressTickets($regularUsers, $cars, $problems, $allMechanics);
        $this->createCompletedTickets($regularUsers, $cars, $problems, $allMechanics);
        $this->createClosedTickets($regularUsers, $cars, $problems, $allMechanics);

        $this->command->info('Tickets seeded successfully!');
    }

    /**
     * Create open tickets (not yet assigned).
     */
    private function createOpenTickets($users, $cars, $problems): void
    {
        for ($i = 0; $i < 5; $i++) {
            $user = $users->random();
            $userCars = $cars->where('user_id', $user->id);

            if ($userCars->isEmpty()) {
                continue;
            }

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'mechanic_id' => null,
                'car_id' => $userCars->random()->id,
                'status' => 'open',
                'priority' => ['low', 'medium', 'high', 'urgent'][rand(0, 3)],
                'description' => $this->generateDescription('open'),
                'accepted_at' => null,
                'completed_at' => null,
            ]);

            // Attach 1-3 problems to the ticket
            $selectedProblems = $problems->random(rand(1, 3));
            foreach ($selectedProblems as $problem) {
                $ticket->problems()->attach($problem->id, [
                    'notes' => $this->generateProblemNotes($problem),
                ]);
            }
        }
    }

    /**
     * Create assigned tickets (mechanic assigned but not started).
     */
    private function createAssignedTickets($users, $cars, $problems, $mechanics): void
    {
        if ($mechanics->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 4; $i++) {
            $user = $users->random();
            $userCars = $cars->where('user_id', $user->id);

            if ($userCars->isEmpty()) {
                continue;
            }

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'mechanic_id' => $mechanics->random()->id,
                'car_id' => $userCars->random()->id,
                'status' => 'assigned',
                'priority' => ['low', 'medium', 'high', 'urgent'][rand(0, 3)],
                'description' => $this->generateDescription('assigned'),
                'accepted_at' => now()->subDays(rand(0, 2)),
                'completed_at' => null,
            ]);

            $selectedProblems = $problems->random(rand(1, 2));
            foreach ($selectedProblems as $problem) {
                $ticket->problems()->attach($problem->id, [
                    'notes' => $this->generateProblemNotes($problem),
                ]);
            }
        }
    }

    /**
     * Create in-progress tickets (actively being worked on).
     */
    private function createInProgressTickets($users, $cars, $problems, $mechanics): void
    {
        if ($mechanics->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 6; $i++) {
            $user = $users->random();
            $userCars = $cars->where('user_id', $user->id);

            if ($userCars->isEmpty()) {
                continue;
            }

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'mechanic_id' => $mechanics->random()->id,
                'car_id' => $userCars->random()->id,
                'status' => 'in_progress',
                'priority' => ['low', 'medium', 'high', 'urgent'][rand(0, 3)],
                'description' => $this->generateDescription('in_progress'),
                'accepted_at' => now()->subDays(rand(1, 5)),
                'completed_at' => null,
            ]);

            $selectedProblems = $problems->random(rand(1, 3));
            foreach ($selectedProblems as $problem) {
                $ticket->problems()->attach($problem->id, [
                    'notes' => $this->generateProblemNotes($problem) . ' Work in progress...',
                ]);
            }
        }
    }

    /**
     * Create completed tickets (work done, awaiting closure).
     */
    private function createCompletedTickets($users, $cars, $problems, $mechanics): void
    {
        if ($mechanics->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 8; $i++) {
            $user = $users->random();
            $userCars = $cars->where('user_id', $user->id);

            if ($userCars->isEmpty()) {
                continue;
            }

            $acceptedAt = now()->subDays(rand(7, 30));
            $completedAt = $acceptedAt->copy()->addDays(rand(1, 7));

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'mechanic_id' => $mechanics->random()->id,
                'car_id' => $userCars->random()->id,
                'status' => 'completed',
                'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                'description' => $this->generateDescription('completed'),
                'accepted_at' => $acceptedAt,
                'completed_at' => $completedAt,
            ]);

            $selectedProblems = $problems->random(rand(1, 3));
            foreach ($selectedProblems as $problem) {
                $ticket->problems()->attach($problem->id, [
                    'notes' => $this->generateProblemNotes($problem) . ' Issue resolved successfully.',
                ]);
            }
        }
    }

    /**
     * Create closed tickets (fully resolved and closed).
     */
    private function createClosedTickets($users, $cars, $problems, $mechanics): void
    {
        if ($mechanics->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 12; $i++) {
            $user = $users->random();
            $userCars = $cars->where('user_id', $user->id);

            if ($userCars->isEmpty()) {
                continue;
            }

            $acceptedAt = now()->subDays(rand(30, 180));
            $completedAt = $acceptedAt->copy()->addDays(rand(1, 10));

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'mechanic_id' => $mechanics->random()->id,
                'car_id' => $userCars->random()->id,
                'status' => 'closed',
                'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                'description' => $this->generateDescription('closed'),
                'accepted_at' => $acceptedAt,
                'completed_at' => $completedAt,
            ]);

            $selectedProblems = $problems->random(rand(1, 4));
            foreach ($selectedProblems as $problem) {
                $ticket->problems()->attach($problem->id, [
                    'notes' => $this->generateProblemNotes($problem) . ' Customer satisfied, ticket closed.',
                ]);
            }
        }
    }

    /**
     * Generate realistic description based on ticket status.
     */
    private function generateDescription(string $status): string
    {
        $descriptions = [
            'open' => [
                'My car is making a strange noise when I accelerate. It started yesterday and seems to be getting worse.',
                'The check engine light came on this morning. The car seems to be running fine otherwise, but I\'m concerned.',
                'I noticed fluid leaking under my car after parking overnight. Not sure what it is.',
                'Brakes feel soft and spongy. Takes more pressure than usual to stop the car.',
                'Car has been hard to start in the mornings. Battery might need checking.',
            ],
            'assigned' => [
                'Vehicle brought in for inspection. Customer reports intermittent issues with starting.',
                'Initial assessment scheduled. Customer describes unusual sounds from engine bay.',
                'Diagnostic appointment set. Will investigate electrical system issues.',
                'Customer waiting for inspection results. Preliminary check suggests brake system needs attention.',
            ],
            'in_progress' => [
                'Currently diagnosing the issue. Ran initial tests and found several error codes.',
                'Parts ordered and awaiting delivery. Should be able to complete repairs by end of week.',
                'Disassembled the component to inspect internal damage. Will provide update soon.',
                'Performing comprehensive inspection. Multiple systems need attention.',
            ],
            'completed' => [
                'All repairs completed successfully. Vehicle tested and running smoothly.',
                'Fixed the identified issues. Performed quality check and everything looks good.',
                'Repairs finished. Replaced worn parts and conducted full system test.',
                'Work completed as scheduled. Ready for customer pickup.',
            ],
            'closed' => [
                'Customer picked up vehicle. All work completed to satisfaction.',
                'Ticket closed after successful repair and customer approval.',
                'Work completed and verified. Customer happy with the results.',
                'All issues resolved. Vehicle returned to owner in excellent condition.',
            ],
        ];

        return $descriptions[$status][array_rand($descriptions[$status])];
    }

    /**
     * Generate problem-specific notes.
     */
    private function generateProblemNotes($problem): string
    {
        $noteTemplates = [
            'Customer reports: ' . strtolower($problem->name),
            'Diagnosed issue: ' . strtolower($problem->name),
            'Found problem with ' . strtolower($problem->category) . ' system',
            'Requires attention: ' . strtolower($problem->name),
            'Issue identified as ' . strtolower($problem->name),
        ];

        return $noteTemplates[array_rand($noteTemplates)];
    }
}
