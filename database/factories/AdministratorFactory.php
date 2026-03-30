<?php

namespace Database\Factories;

use App\Models\Administrator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Administrator>
 */
class AdministratorFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'display_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'role' => 'compliance_admin',
            'mfa_enabled' => false,
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }
}
