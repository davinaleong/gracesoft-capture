<?php

use App\Models\Account;
use App\Models\Plan;
use App\Models\StripeWebhookEvent;
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
        'id' => 'evt_sub_updated_123',
        'object' => 'event',
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
        'id' => 'evt_invalid_sig_123',
        'object' => 'event',
        'type' => 'invoice.paid',
        'data' => ['object' => []],
    ], [
        'Stripe-Signature' => 't=1,v1=invalid',
    ])->assertStatus(401);
});

test('stripe webhook processes duplicate event only once', function () {
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

    $account = Account::factory()->create([
        'stripe_customer_id' => 'cus_dup_123',
    ]);

    Subscription::factory()->create([
        'id' => (string) Str::uuid(),
        'account_id' => $account->id,
        'plan_id' => $freePlan->id,
        'stripe_subscription_id' => 'sub_dup_123',
        'status' => 'active',
    ]);

    $payload = [
        'id' => 'evt_dup_123',
        'object' => 'event',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_dup_123',
                'customer' => 'cus_dup_123',
                'status' => 'past_due',
                'current_period_end' => now()->addDays(10)->timestamp,
                'items' => [
                    'data' => [
                        ['price' => ['id' => null]],
                    ],
                ],
            ],
        ],
    ];

    $signature = stripeSignature($payload, 'whsec_test_secret');

    $this->postJson(route('billing.webhooks.stripe'), $payload, [
        'Stripe-Signature' => $signature,
    ])->assertOk();

    $this->postJson(route('billing.webhooks.stripe'), $payload, [
        'Stripe-Signature' => $signature,
    ])->assertOk();

    expect(StripeWebhookEvent::query()->where('event_id', 'evt_dup_123')->count())->toBe(1);
    expect(StripeWebhookEvent::query()->where('event_id', 'evt_dup_123')->firstOrFail()->processed_at)->not->toBeNull();
    expect(Subscription::query()->where('stripe_subscription_id', 'sub_dup_123')->firstOrFail()->status)->toBe('past_due');
});

test('stripe price webhook syncs plan price and product mapping', function () {
    $growth = Plan::query()->where('slug', 'growth')->firstOrFail();

    $payload = [
        'id' => 'evt_price_sync_123',
        'object' => 'event',
        'type' => 'price.updated',
        'data' => [
            'object' => [
                'id' => 'price_growth_live_123',
                'object' => 'price',
                'lookup_key' => 'growth',
                'metadata' => [
                    'capture_plan_slug' => 'growth',
                ],
                'product' => [
                    'id' => 'prod_growth_live_123',
                    'object' => 'product',
                    'name' => 'Growth',
                ],
            ],
        ],
    ];

    $this->postJson(route('billing.webhooks.stripe'), $payload, [
        'Stripe-Signature' => stripeSignature($payload, 'whsec_test_secret'),
    ])->assertOk();

    $growth->refresh();

    expect($growth->stripe_price_id)->toBe('price_growth_live_123');
    expect($growth->stripe_product_id)->toBe('prod_growth_live_123');
});

test('stripe product webhook syncs plan product mapping', function () {
    $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

    $payload = [
        'id' => 'evt_product_sync_123',
        'object' => 'event',
        'type' => 'product.updated',
        'data' => [
            'object' => [
                'id' => 'prod_pro_live_123',
                'object' => 'product',
                'name' => 'Pro',
                'metadata' => [
                    'capture_plan_slug' => 'pro',
                ],
            ],
        ],
    ];

    $this->postJson(route('billing.webhooks.stripe'), $payload, [
        'Stripe-Signature' => stripeSignature($payload, 'whsec_test_secret'),
    ])->assertOk();

    $pro->refresh();

    expect($pro->stripe_product_id)->toBe('prod_pro_live_123');
});

function stripeSignature(array $payload, string $secret): string
{
    $timestamp = now()->timestamp;
    $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
    $digest = hash_hmac('sha256', $timestamp . '.' . $encoded, $secret);

    return 't=' . $timestamp . ',v1=' . $digest;
}
