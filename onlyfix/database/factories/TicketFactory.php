<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['open', 'assigned', 'in_progress', 'completed', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        return [
            'user_id' => User::factory(),
            'mechanic_id' => null,
            'car_id' => Car::factory(),
            'status' => 'open',
            'priority' => $this->faker->randomElement($priorities),
            'description' => $this->faker->paragraph(3),
            'accepted_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the ticket is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'mechanic_id' => null,
            'accepted_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the ticket is assigned to a mechanic.
     */
    public function assigned(?User $mechanic = null): static
    {
        return $this->state(function (array $attributes) use ($mechanic) {
            $mechanicId = $mechanic?->id ?? User::factory()->asMechanic()->create()->id;

            return [
                'status' => 'assigned',
                'mechanic_id' => $mechanicId,
                'accepted_at' => now()->subHours($this->faker->numberBetween(1, 48)),
                'completed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the ticket is in progress.
     */
    public function inProgress(?User $mechanic = null): static
    {
        return $this->state(function (array $attributes) use ($mechanic) {
            $mechanicId = $mechanic?->id ?? User::factory()->asMechanic()->create()->id;

            return [
                'status' => 'in_progress',
                'mechanic_id' => $mechanicId,
                'accepted_at' => now()->subHours($this->faker->numberBetween(2, 72)),
                'completed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the ticket is completed.
     */
    public function completed(?User $mechanic = null): static
    {
        return $this->state(function (array $attributes) use ($mechanic) {
            $mechanicId = $mechanic?->id ?? User::factory()->asMechanic()->create()->id;
            $acceptedAt = now()->subDays($this->faker->numberBetween(1, 30));

            return [
                'status' => 'completed',
                'mechanic_id' => $mechanicId,
                'accepted_at' => $acceptedAt,
                'completed_at' => $acceptedAt->addHours($this->faker->numberBetween(2, 48)),
            ];
        });
    }

    /**
     * Indicate that the ticket is closed.
     */
    public function closed(?User $mechanic = null): static
    {
        return $this->state(function (array $attributes) use ($mechanic) {
            $mechanicId = $mechanic?->id ?? User::factory()->asMechanic()->create()->id;
            $acceptedAt = now()->subDays($this->faker->numberBetween(1, 30));

            return [
                'status' => 'closed',
                'mechanic_id' => $mechanicId,
                'accepted_at' => $acceptedAt,
                'completed_at' => $acceptedAt->addHours($this->faker->numberBetween(2, 48)),
            ];
        });
    }

    /**
     * Indicate the priority level.
     */
    public function priority(string $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    /**
     * Set urgent priority.
     */
    public function urgent(): static
    {
        return $this->priority('urgent');
    }

    /**
     * Set high priority.
     */
    public function high(): static
    {
        return $this->priority('high');
    }

    /**
     * Set low priority.
     */
    public function low(): static
    {
        return $this->priority('low');
    }

    /**
     * Create ticket for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create ticket for a specific car.
     */
    public function forCar(Car $car): static
    {
        return $this->state(fn (array $attributes) => [
            'car_id' => $car->id,
            'user_id' => $car->user_id,
        ]);
    }
}
