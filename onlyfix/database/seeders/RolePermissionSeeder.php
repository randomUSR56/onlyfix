<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for ticket management
        $permissions = [
            // Ticket permissions
            'view own tickets',
            'create tickets',
            'update own tickets',
            'delete own tickets',

            'view all tickets',
            'accept tickets',
            'update any ticket',
            'delete any ticket',

            // User management permissions (admin only)
            'manage users',
            'reset passwords',
            'manage roles',
            'view all users',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // User role - basic ticket operations on own tickets
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo([
            'view own tickets',
            'create tickets',
            'update own tickets',
            'delete own tickets',
        ]);

        // Mechanic role - can view and manage all tickets
        $mechanicRole = Role::create(['name' => 'mechanic']);
        $mechanicRole->givePermissionTo([
            'view own tickets',
            'create tickets',
            'update own tickets',
            'delete own tickets',
            'view all tickets',
            'accept tickets',
            'update any ticket',
        ]);

        // Admin role - has all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
    }
}
