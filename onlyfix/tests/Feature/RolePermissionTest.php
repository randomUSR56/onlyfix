<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Seed roles and permissions for each test
    $this->seed(RolePermissionSeeder::class);

    // Clear permission cache before each test
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

test('user can be assigned a role', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    expect($user->hasRole('user'))->toBeTrue();
    expect($user->hasRole('admin'))->toBeFalse();
});

test('admin has all permissions', function () {
    $admin = User::factory()->asAdmin()->create();

    expect($admin->hasRole('admin'))->toBeTrue();
    expect($admin->can('manage users'))->toBeTrue();
    expect($admin->can('view all tickets'))->toBeTrue();
    expect($admin->can('reset passwords'))->toBeTrue();
});

test('mechanic has ticket management permissions', function () {
    $mechanic = User::factory()->asMechanic()->create();

    expect($mechanic->hasRole('mechanic'))->toBeTrue();
    expect($mechanic->can('view all tickets'))->toBeTrue();
    expect($mechanic->can('accept tickets'))->toBeTrue();
    expect($mechanic->can('manage users'))->toBeFalse();
});

test('regular user has limited permissions', function () {
    $user = User::factory()->asUser()->create();

    expect($user->hasRole('user'))->toBeTrue();
    expect($user->can('create tickets'))->toBeTrue();
    expect($user->can('view own tickets'))->toBeTrue();
    expect($user->can('view all tickets'))->toBeFalse();
    expect($user->can('manage users'))->toBeFalse();
});

test('user policy restricts non-admin from viewing all users', function () {
    $user = User::factory()->asUser()->create();
    $otherUser = User::factory()->create();

    expect($user->can('viewAny', User::class))->toBeFalse();
    expect($user->can('view', $otherUser))->toBeFalse();
});

test('admin can view all users', function () {
    $admin = User::factory()->asAdmin()->create();
    $otherUser = User::factory()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue();
    expect($admin->can('view', $otherUser))->toBeTrue();
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->asAdmin()->create();

    expect($admin->can('delete', $admin))->toBeFalse();
});

test('user can update their own profile', function () {
    $user = User::factory()->asUser()->create();

    expect($user->can('update', $user))->toBeTrue();
});

test('user cannot update other users profiles', function () {
    $user = User::factory()->asUser()->create();
    $otherUser = User::factory()->create();

    expect($user->can('update', $otherUser))->toBeFalse();
});

test('roles have correct permission hierarchy', function () {
    $user = User::factory()->asUser()->create();
    $mechanic = User::factory()->asMechanic()->create();
    $admin = User::factory()->asAdmin()->create();

    // User permissions count
    expect($user->getAllPermissions()->count())->toBe(4);

    // Mechanic has more permissions than user
    expect($mechanic->getAllPermissions()->count())->toBeGreaterThan(4);

    // Admin has all permissions
    expect($admin->getAllPermissions()->count())->toBe(Permission::count());
});

