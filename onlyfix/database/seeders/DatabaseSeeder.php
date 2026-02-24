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

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => 'password', 'email_verified_at' => now()]
        );
        $admin->assignRole('admin');

        $mechanic = User::firstOrCreate(
            ['email' => 'mechanic@example.com'],
            ['name' => 'Mechanic User', 'password' => 'password', 'email_verified_at' => now()]
        );
        $mechanic->assignRole('mechanic');

        // Create additional mechanics
        $mechanic2 = User::firstOrCreate(
            ['email' => 'sarah.johnson@example.com'],
            ['name' => 'Sarah Johnson', 'password' => 'password', 'email_verified_at' => now()]
        );
        $mechanic2->assignRole('mechanic');

        $mechanic3 = User::firstOrCreate(
            ['email' => 'mike.davis@example.com'],
            ['name' => 'Mike Davis', 'password' => 'password', 'email_verified_at' => now()]
        );
        $mechanic3->assignRole('mechanic');

        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'email_verified_at' => now()]
        );
        $user->assignRole('user');

        // Create additional regular users if they don't exist
        for ($i = 1; $i <= 10; $i++) {
            $email = "user{$i}@example.com";
            $u = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Regular User {$i}",
                    'password' => 'password',
                    'email_verified_at' => now()
                ]
            );
            if (!$u->hasRole('user')) {
                $u->assignRole('user');
            }
        }

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
