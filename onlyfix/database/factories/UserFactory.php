<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Indicate that the user should have two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => Str::random(32), // Use a longer secret for TOTP
            'two_factor_recovery_codes' => encrypt(json_encode(collect(range(1, 8))->map(fn () => Str::random(10))->toArray())),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user should have the 'user' role.
     */
    public function asUser(): static
    {
        return $this->afterCreating(fn ($user) => $user->assignRole('user'));
    }

    /**
     * Indicate that the user should have the 'mechanic' role.
     */
    public function asMechanic(): static
    {
        return $this->afterCreating(fn ($user) => $user->assignRole('mechanic'));
    }

    /**
     * Indicate that the user should have the 'admin' role.
     */
    public function asAdmin(): static
    {
        return $this->afterCreating(fn ($user) => $user->assignRole('admin'));
    }
}