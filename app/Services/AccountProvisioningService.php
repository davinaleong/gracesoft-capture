<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMembership;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountProvisioningService
{
    public function provisionForUser(User $user, ?string $accountName = null): Account
    {
        return DB::transaction(function () use ($user, $accountName): Account {
            $account = Account::query()->create([
                'id' => (string) Str::uuid(),
                'name' => $accountName ?: ($user->name . "'s Workspace"),
                'owner_user_id' => $user->id,
                'stripe_customer_id' => null,
            ]);

            AccountMembership::query()->create([
                'account_id' => $account->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            $freePlan = Plan::query()->where('slug', 'free')->first();

            if (! $freePlan) {
                $freePlan = Plan::query()->create([
                    'id' => (string) Str::uuid(),
                    'name' => 'Free',
                    'slug' => 'free',
                    'stripe_price_id' => null,
                    'stripe_product_id' => null,
                    'max_users' => 1,
                    'max_items' => 50,
                    'max_replies' => 100,
                ]);
            }

            Subscription::query()->create([
                'id' => (string) Str::uuid(),
                'account_id' => $account->id,
                'plan_id' => $freePlan->id,
                'stripe_subscription_id' => null,
                'status' => 'active',
                'current_period_end' => null,
            ]);

            return $account;
        });
    }
}
