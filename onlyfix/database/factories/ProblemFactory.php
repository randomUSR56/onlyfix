<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Problem>
 */
class ProblemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Match the enum values from the migration
        $categories = ['engine', 'transmission', 'electrical', 'brakes', 'suspension', 'steering', 'body', 'other'];
        $problems = [
            'engine' => ['Oil leak', 'Overheating', 'Strange noise', 'Check engine light', 'Poor performance'],
            'transmission' => ['Slipping gears', 'Hard shifting', 'Fluid leak', 'Grinding noise'],
            'brakes' => ['Squeaking', 'Grinding', 'Soft pedal', 'Pulling to one side', 'ABS light on'],
            'electrical' => ['Battery dead', 'Alternator failure', 'Starter issues', 'Lighting problems'],
            'suspension' => ['Rough ride', 'Uneven tire wear', 'Clunking noise', 'Vehicle pulls'],
            'steering' => ['Hard to turn', 'Loose steering', 'Steering wheel vibration', 'Power steering leak'],
            'body' => ['Rust damage', 'Dents', 'Paint issues', 'Door alignment'],
            'other' => ['Air conditioning issues', 'Heater problems', 'Window regulators', 'General maintenance'],
        ];

        $category = $this->faker->randomElement($categories);
        $problemName = $this->faker->randomElement($problems[$category]);

        // Add suffix to ensure uniqueness when creating many problems
        $uniqueName = $problemName . ' - ' . $this->faker->unique()->numerify('###');

        return [
            'name' => $uniqueName,
            'category' => $category,
            'description' => $this->faker->sentence(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the problem is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a problem with a specific category.
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
