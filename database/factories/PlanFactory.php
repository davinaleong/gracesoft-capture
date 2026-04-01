<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        $slug = fake()->unique()->slug();

        return [
            'id' => (string) Str::uuid(),
            'name' => Str::title(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'stripe_price_id' => null,
            'stripe_product_id' => null,
            'max_users' => 1,
            'max_items' => 100,
            'max_replies' => 100,
        ];
    }
}
