<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Car>
 */
class CarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $makes = ['Toyota', 'Honda', 'Ford', 'Chevrolet', 'BMW', 'Mercedes', 'Audi', 'Volkswagen', 'Nissan', 'Mazda'];
        $colors = ['Red', 'Blue', 'Black', 'White', 'Silver', 'Gray', 'Green', 'Yellow'];

        return [
            'user_id' => User::factory(),
            'make' => $this->faker->randomElement($makes),
            'model' => $this->faker->word() . ' ' . $this->faker->numberBetween(100, 500),
            'year' => $this->faker->numberBetween(2000, 2025),
            'license_plate' => strtoupper($this->faker->unique()->bothify('???-####')),
            'vin' => strtoupper($this->faker->unique()->bothify('?????????????####')),
            'color' => $this->faker->randomElement($colors),
        ];
    }

    /**
     * Indicate that the car belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
