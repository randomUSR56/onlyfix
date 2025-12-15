<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // 1. Seed roles and permissions first
        $this->command->info('📋 Seeding roles and permissions...');
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // 2. Create test users with different roles
        $this->command->info('👤 Creating users...');

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        $mechanic = User::factory()->create([
            'name' => 'Mechanic User',
            'email' => 'mechanic@example.com',
        ]);
        $mechanic->assignRole('mechanic');

        // Create additional mechanics
        $mechanic2 = User::factory()->create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah.johnson@example.com',
        ]);
        $mechanic2->assignRole('mechanic');

        $mechanic3 = User::factory()->create([
            'name' => 'Mike Davis',
            'email' => 'mike.davis@example.com',
        ]);
        $mechanic3->assignRole('mechanic');

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $user->assignRole('user');

        // Create additional regular users
        User::factory()->count(10)->create()->each(function ($user) {
            $user->assignRole('user');
        });

        $this->command->info('✅ Users created successfully!');

        // 3. Seed problems (all available car problems)
        $this->command->info('🔧 Seeding car problems...');
        $this->call([
            ProblemSeeder::class,
        ]);

        // 4. Seed cars (multiple cars per user)
        $this->command->info('🚗 Seeding cars...');
        $this->call([
            CarSeeder::class,
        ]);

        // 5. Seed tickets with various statuses and problems
        $this->command->info('🎫 Seeding tickets...');
        $this->call([
            TicketSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✨ Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('🔑 Test Accounts:');
        $this->command->info('   Admin:    admin@example.com / password');
        $this->command->info('   Mechanic: mechanic@example.com / password');
        $this->command->info('   User:     test@example.com / password');
        $this->command->info('');
    }
}
