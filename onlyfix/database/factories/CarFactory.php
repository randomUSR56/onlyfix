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
        $carData = $this->getRandomCarData();

        return [
            'user_id' => User::factory(),
            'make' => $carData['make'],
            'model' => $carData['model'],
            'year' => $this->faker->numberBetween(2000, 2025),
            'license_plate' => $this->generateLicensePlate(),
            'vin' => $this->generateVIN(),
            'color' => $this->faker->randomElement([
                'Red', 'Blue', 'Black', 'White', 'Silver', 'Gray',
                'Green', 'Yellow', 'Orange', 'Brown', 'Beige', 'Purple'
            ]),
        ];
    }

    /**
     * Get realistic car make and model combinations.
     */
    private function getRandomCarData(): array
    {
        $cars = [
            ['make' => 'Toyota', 'model' => 'Camry'],
            ['make' => 'Toyota', 'model' => 'Corolla'],
            ['make' => 'Toyota', 'model' => 'RAV4'],
            ['make' => 'Toyota', 'model' => 'Highlander'],
            ['make' => 'Toyota', 'model' => 'Tacoma'],
            ['make' => 'Honda', 'model' => 'Civic'],
            ['make' => 'Honda', 'model' => 'Accord'],
            ['make' => 'Honda', 'model' => 'CR-V'],
            ['make' => 'Honda', 'model' => 'Pilot'],
            ['make' => 'Ford', 'model' => 'F-150'],
            ['make' => 'Ford', 'model' => 'Mustang'],
            ['make' => 'Ford', 'model' => 'Explorer'],
            ['make' => 'Ford', 'model' => 'Escape'],
            ['make' => 'Ford', 'model' => 'Focus'],
            ['make' => 'Chevrolet', 'model' => 'Silverado'],
            ['make' => 'Chevrolet', 'model' => 'Malibu'],
            ['make' => 'Chevrolet', 'model' => 'Equinox'],
            ['make' => 'Chevrolet', 'model' => 'Tahoe'],
            ['make' => 'Chevrolet', 'model' => 'Camaro'],
            ['make' => 'BMW', 'model' => '3 Series'],
            ['make' => 'BMW', 'model' => '5 Series'],
            ['make' => 'BMW', 'model' => 'X3'],
            ['make' => 'BMW', 'model' => 'X5'],
            ['make' => 'Mercedes-Benz', 'model' => 'C-Class'],
            ['make' => 'Mercedes-Benz', 'model' => 'E-Class'],
            ['make' => 'Mercedes-Benz', 'model' => 'GLC'],
            ['make' => 'Audi', 'model' => 'A4'],
            ['make' => 'Audi', 'model' => 'Q5'],
            ['make' => 'Audi', 'model' => 'A6'],
            ['make' => 'Volkswagen', 'model' => 'Jetta'],
            ['make' => 'Volkswagen', 'model' => 'Passat'],
            ['make' => 'Volkswagen', 'model' => 'Tiguan'],
            ['make' => 'Nissan', 'model' => 'Altima'],
            ['make' => 'Nissan', 'model' => 'Sentra'],
            ['make' => 'Nissan', 'model' => 'Rogue'],
            ['make' => 'Nissan', 'model' => 'Pathfinder'],
            ['make' => 'Mazda', 'model' => 'Mazda3'],
            ['make' => 'Mazda', 'model' => 'Mazda6'],
            ['make' => 'Mazda', 'model' => 'CX-5'],
            ['make' => 'Mazda', 'model' => 'CX-9'],
            ['make' => 'Subaru', 'model' => 'Outback'],
            ['make' => 'Subaru', 'model' => 'Forester'],
            ['make' => 'Subaru', 'model' => 'Impreza'],
            ['make' => 'Hyundai', 'model' => 'Elantra'],
            ['make' => 'Hyundai', 'model' => 'Sonata'],
            ['make' => 'Hyundai', 'model' => 'Tucson'],
            ['make' => 'Kia', 'model' => 'Optima'],
            ['make' => 'Kia', 'model' => 'Sorento'],
            ['make' => 'Kia', 'model' => 'Sportage'],
            ['make' => 'Jeep', 'model' => 'Wrangler'],
            ['make' => 'Jeep', 'model' => 'Cherokee'],
            ['make' => 'Jeep', 'model' => 'Grand Cherokee'],
        ];

        return $this->faker->randomElement($cars);
    }

    /**
     * Generate a realistic license plate.
     */
    private function generateLicensePlate(): string
    {
        $formats = [
            'ABC-####',  // Standard format
            '##-ABC-##', // Alternative format
            'ABC####',   // No dashes
            '###-ABC',   // Number first
        ];

        return strtoupper($this->faker->unique()->bothify($this->faker->randomElement($formats)));
    }

    /**
     * Generate a realistic VIN (Vehicle Identification Number).
     */
    private function generateVIN(): string
    {
        // VINs are 17 characters: digits and uppercase letters (excluding I, O, Q)
        $validChars = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';
        $vin = '';

        for ($i = 0; $i < 17; $i++) {
            $vin .= $validChars[rand(0, strlen($validChars) - 1)];
        }

        return $this->faker->unique()->regexify($vin);
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

    /**
     * Create an older car (15-25 years old).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $this->faker->numberBetween(2000, 2010),
        ]);
    }

    /**
     * Create a newer car (0-5 years old).
     */
    public function newer(): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $this->faker->numberBetween(2020, 2025),
        ]);
    }

    /**
     * Create a car with a specific make.
     */
    public function withMake(string $make): static
    {
        return $this->state(fn (array $attributes) => [
            'make' => $make,
        ]);
    }
}
