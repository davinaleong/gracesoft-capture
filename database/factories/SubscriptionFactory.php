<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'account_id' => Account::factory(),
            'plan_id' => Plan::factory(),
            'stripe_subscription_id' => null,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ];
    }
}
