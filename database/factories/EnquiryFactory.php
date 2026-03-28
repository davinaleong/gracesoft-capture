<?php

namespace Database\Factories;

use App\Models\Enquiry;
use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'account_id' => fake()->uuid(),
            'application_id' => fake()->uuid(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'status' => 'new',
            'metadata' => [
                'ip' => fake()->ipv4(),
            ],
        ];
    }
}
