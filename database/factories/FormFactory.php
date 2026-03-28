<?php

namespace Database\Factories;

use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => (string) Str::uuid(),
            'application_id' => (string) Str::uuid(),
            'name' => fake()->words(3, true),
            'public_token' => 'frm_' . Str::lower(Str::random(24)),
            'is_active' => true,
            'settings' => [
                'theme' => 'default',
            ],
        ];
    }
}
