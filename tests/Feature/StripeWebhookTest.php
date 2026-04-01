<?php

use App\Models\Account;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.stripe.webhook_secret', 'whsec_test_secret');
});

test('stripe subscription updated webhook syncs plan and status', function () {
    $freePlan = Plan::query()->firstOrCreate(
        ['slug' => 'free'],
        [
            'id' => (string) Str::uuid(),
            'name' => 'Free',
            'stripe_price_id' => null,
            'stripe_product_id' => null,
            'max_users' => 1,
            'max_items' => 50,
            'max_replies' => 100,
        ]
    );

    $proPlan = Plan::query()->firstOrCreate(
        ['slug' => 'pro'],
        [
            'id' => (string) Str::uuid(),
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_123',
            'stripe_product_id' => null,
            'max_users' => 20,
            'max_items' => 5000,
            'max_replies' => 20000,
        ]
    );

    $proPlan->forceFill(['stripe_price_id' => 'price_pro_123'])->save();

    $account = Account::factory()->create([
        'stripe_customer_id' => 'cus_123',
    ]);

    Subscription::factory()->create([
        'id' => (string) Str::uuid(),
        'account_id' => $account->id,
        'plan_id' => $freePlan->id,
        'stripe_subscription_id' => 'sub_123',
        'status' => 'active',
    ]);

    $payload = [
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_123',
                'customer' => 'cus_123',
                'status' => 'past_due',
                'current_period_end' => now()->addDays(10)->timestamp,
                'items' => [
                    'data' => [
                        ['price' => ['id' => 'price_pro_123']],
                    ],
                ],
            ],
        ],
    ];

    $this->postJson(
        route('billing.webhooks.stripe'),
        $payload,
        ['Stripe-Signature' => stripeSignature($payload, 'whsec_test_secret')]
    )->assertOk();

    $subscription = Subscription::query()->where('stripe_subscription_id', 'sub_123')->firstOrFail();

    expect($subscription->status)->toBe('past_due');
    expect($subscription->plan_id)->toBe($proPlan->id);
});

test('stripe webhook rejects invalid signature', function () {
    $this->postJson(route('billing.webhooks.stripe'), [
        'type' => 'invoice.paid',
        'data' => ['object' => []],
    ], [
        'Stripe-Signature' => 't=1,v1=invalid',
    ])->assertStatus(401);
});

function stripeSignature(array $payload, string $secret): string
{
    $timestamp = now()->timestamp;
    $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
    $digest = hash_hmac('sha256', $timestamp . '.' . $encoded, $secret);

    return 't=' . $timestamp . ',v1=' . $digest;
}
