<?php

namespace Database\Seeders;

use App\Models\Problem;
use Illuminate\Database\Seeder;

class ProblemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $problems = [
            // Engine problems
            ['name' => 'Engine oil leak', 'category' => 'engine', 'description' => 'Oil leaking from engine components', 'is_active' => true],
            ['name' => 'Engine overheating', 'category' => 'engine', 'description' => 'Engine temperature exceeds normal range', 'is_active' => true],
            ['name' => 'Check engine light on', 'category' => 'engine', 'description' => 'Dashboard warning light illuminated', 'is_active' => true],
            ['name' => 'Engine misfire', 'category' => 'engine', 'description' => 'Engine running rough or misfiring', 'is_active' => true],
            ['name' => 'Poor fuel economy', 'category' => 'engine', 'description' => 'Vehicle consuming excessive fuel', 'is_active' => true],
            ['name' => 'Engine making strange noise', 'category' => 'engine', 'description' => 'Unusual sounds coming from engine', 'is_active' => true],
            ['name' => 'Hard to start engine', 'category' => 'engine', 'description' => 'Engine takes multiple attempts to start', 'is_active' => true],

            // Transmission problems
            ['name' => 'Transmission slipping', 'category' => 'transmission', 'description' => 'Transmission not engaging properly between gears', 'is_active' => true],
            ['name' => 'Hard shifting', 'category' => 'transmission', 'description' => 'Difficulty changing gears', 'is_active' => true],
            ['name' => 'Transmission fluid leak', 'category' => 'transmission', 'description' => 'Red fluid leaking from transmission', 'is_active' => true],
            ['name' => 'Grinding noise when shifting', 'category' => 'transmission', 'description' => 'Unusual grinding sound during gear changes', 'is_active' => true],
            ['name' => 'Delayed engagement', 'category' => 'transmission', 'description' => 'Transmission hesitates before engaging', 'is_active' => true],

            // Electrical problems
            ['name' => 'Battery dead', 'category' => 'electrical', 'description' => 'Battery not holding charge', 'is_active' => true],
            ['name' => 'Alternator failure', 'category' => 'electrical', 'description' => 'Alternator not charging battery properly', 'is_active' => true],
            ['name' => 'Starter motor issues', 'category' => 'electrical', 'description' => 'Starter not engaging or clicking', 'is_active' => true],
            ['name' => 'Headlights not working', 'category' => 'electrical', 'description' => 'One or both headlights not functioning', 'is_active' => true],
            ['name' => 'Dashboard lights malfunction', 'category' => 'electrical', 'description' => 'Warning lights not working correctly', 'is_active' => true],
            ['name' => 'Power windows not working', 'category' => 'electrical', 'description' => 'Electric windows not operating', 'is_active' => true],
            ['name' => 'Central locking failure', 'category' => 'electrical', 'description' => 'Door locks not responding to remote or switch', 'is_active' => true],

            // Brake problems
            ['name' => 'Squeaking brakes', 'category' => 'brakes', 'description' => 'High-pitched noise when applying brakes', 'is_active' => true],
            ['name' => 'Grinding brakes', 'category' => 'brakes', 'description' => 'Metal-on-metal grinding sound when braking', 'is_active' => true],
            ['name' => 'Soft brake pedal', 'category' => 'brakes', 'description' => 'Brake pedal feels spongy or goes to floor', 'is_active' => true],
            ['name' => 'Brake pulls to one side', 'category' => 'brakes', 'description' => 'Vehicle pulls left or right when braking', 'is_active' => true],
            ['name' => 'ABS light on', 'category' => 'brakes', 'description' => 'Anti-lock braking system warning light illuminated', 'is_active' => true],
            ['name' => 'Brake fluid leak', 'category' => 'brakes', 'description' => 'Brake fluid leaking from system', 'is_active' => true],
            ['name' => 'Parking brake stuck', 'category' => 'brakes', 'description' => 'Emergency brake not releasing properly', 'is_active' => true],

            // Suspension problems
            ['name' => 'Rough ride', 'category' => 'suspension', 'description' => 'Vehicle riding rough over bumps', 'is_active' => true],
            ['name' => 'Uneven tire wear', 'category' => 'suspension', 'description' => 'Tires wearing unevenly across tread', 'is_active' => true],
            ['name' => 'Clunking noise over bumps', 'category' => 'suspension', 'description' => 'Loud clunking when driving over uneven surfaces', 'is_active' => true],
            ['name' => 'Vehicle sits lower on one side', 'category' => 'suspension', 'description' => 'Uneven vehicle height indicating suspension failure', 'is_active' => true],
            ['name' => 'Excessive bouncing', 'category' => 'suspension', 'description' => 'Vehicle continues bouncing after going over bumps', 'is_active' => true],
            ['name' => 'Nose dives when braking', 'category' => 'suspension', 'description' => 'Front end drops excessively during braking', 'is_active' => true],

            // Steering problems
            ['name' => 'Hard to turn steering wheel', 'category' => 'steering', 'description' => 'Excessive effort required to turn wheel', 'is_active' => true],
            ['name' => 'Loose steering', 'category' => 'steering', 'description' => 'Steering wheel has excessive play', 'is_active' => true],
            ['name' => 'Steering wheel vibration', 'category' => 'steering', 'description' => 'Wheel shakes or vibrates when driving', 'is_active' => true],
            ['name' => 'Power steering fluid leak', 'category' => 'steering', 'description' => 'Power steering fluid leaking from system', 'is_active' => true],
            ['name' => 'Steering wheel off-center', 'category' => 'steering', 'description' => 'Wheel not straight when driving straight', 'is_active' => true],
            ['name' => 'Whining noise when turning', 'category' => 'steering', 'description' => 'High-pitched whine during steering operation', 'is_active' => true],

            // Body problems
            ['name' => 'Rust damage', 'category' => 'body', 'description' => 'Corrosion on body panels', 'is_active' => true],
            ['name' => 'Dent repair needed', 'category' => 'body', 'description' => 'Body panel dented from impact', 'is_active' => true],
            ['name' => 'Paint scratches', 'category' => 'body', 'description' => 'Surface paint damage requiring repair', 'is_active' => true],
            ['name' => 'Door alignment issues', 'category' => 'body', 'description' => 'Doors not closing or aligning properly', 'is_active' => true],
            ['name' => 'Windshield crack', 'category' => 'body', 'description' => 'Crack or chip in windshield glass', 'is_active' => true],
            ['name' => 'Hood latch broken', 'category' => 'body', 'description' => 'Hood not latching securely', 'is_active' => true],

            // Other problems
            ['name' => 'Air conditioning not cooling', 'category' => 'other', 'description' => 'AC blowing warm air or not working', 'is_active' => true],
            ['name' => 'Heater not working', 'category' => 'other', 'description' => 'Heating system not producing warm air', 'is_active' => true],
            ['name' => 'Window won\'t roll up/down', 'category' => 'other', 'description' => 'Power window stuck or not operating', 'is_active' => true],
            ['name' => 'Exhaust leak', 'category' => 'other', 'description' => 'Exhaust system leaking gases', 'is_active' => true],
            ['name' => 'Unusual exhaust smoke', 'category' => 'other', 'description' => 'Black, white, or blue smoke from exhaust', 'is_active' => true],
            ['name' => 'Fuel gauge not working', 'category' => 'other', 'description' => 'Fuel level indicator not reading correctly', 'is_active' => true],
            ['name' => 'Wiper blades need replacement', 'category' => 'other', 'description' => 'Windshield wipers streaking or not clearing properly', 'is_active' => true],
            ['name' => 'Oil change needed', 'category' => 'other', 'description' => 'Routine oil and filter change required', 'is_active' => true],
            ['name' => 'Tire rotation needed', 'category' => 'other', 'description' => 'Tires need to be rotated for even wear', 'is_active' => true],
            ['name' => 'General inspection', 'category' => 'other', 'description' => 'Comprehensive vehicle safety inspection', 'is_active' => true],
        ];

        foreach ($problems as $problem) {
            Problem::create($problem);
        }

        // Create a few inactive problems (historical)
        Problem::create([
            'name' => 'Carburetor issues',
            'category' => 'engine',
            'description' => 'Old problem type - carburetors no longer common',
            'is_active' => false,
        ]);

        Problem::create([
            'name' => 'Cassette player repair',
            'category' => 'other',
            'description' => 'Obsolete entertainment system',
            'is_active' => false,
        ]);
    }
}