<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users with different roles
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        // Create specific cars for testing
        $testCars = [
            [
                'user_id' => $users->where('email', 'test@example.com')->first()?->id ?? $users->first()->id,
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2020,
                'license_plate' => 'ABC-1234',
                'vin' => 'JT2BF18K5W0123456',
                'color' => 'Silver',
            ],
            [
                'user_id' => $users->where('email', 'test@example.com')->first()?->id ?? $users->first()->id,
                'make' => 'Honda',
                'model' => 'Civic',
                'year' => 2019,
                'license_plate' => 'XYZ-5678',
                'vin' => '2HGFC2F59KH123456',
                'color' => 'Blue',
            ],
            [
                'user_id' => $users->where('email', 'mechanic@example.com')->first()?->id ?? $users->skip(1)->first()?->id ?? $users->first()->id,
                'make' => 'Ford',
                'model' => 'F-150',
                'year' => 2021,
                'license_plate' => 'TRK-9012',
                'vin' => '1FTFW1E84MFA12345',
                'color' => 'Black',
            ],
            [
                'user_id' => $users->where('email', 'admin@example.com')->first()?->id ?? $users->last()->id,
                'make' => 'BMW',
                'model' => '3 Series',
                'year' => 2022,
                'license_plate' => 'BMW-3456',
                'vin' => 'WBA5A5C50GG123456',
                'color' => 'White',
            ],
        ];

        foreach ($testCars as $car) {
            Car::create($car);
        }

        // Create additional random cars for each user
        foreach ($users as $user) {
            // Give each user 1-3 additional cars
            $carCount = rand(1, 3);
            Car::factory()->count($carCount)->create([
                'user_id' => $user->id,
            ]);
        }

        $this->command->info('Cars seeded successfully!');
    }
}
