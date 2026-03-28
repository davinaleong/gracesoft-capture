<?php

namespace Database\Factories;

use App\Models\Enquiry;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enquiry_id' => Enquiry::factory(),
            'user_id' => fake()->uuid(),
            'content' => fake()->paragraph(),
        ];
    }
}
