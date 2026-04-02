<?php

use App\Models\Account;
use App\Models\AccountMembership;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.stripe.secret', 'sk_test_123');
    config()->set('services.stripe.api_base_url', 'https://stripe.test');
    config()->set('services.stripe.checkout_success_url', 'https://app.test/billing/success');
    config()->set('services.stripe.checkout_cancel_url', 'https://app.test/billing/cancel');
    config()->set('services.stripe.portal_return_url', 'https://app.test/manage/forms');
});

test('owner can start stripe checkout for paid plan', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $owner->id]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Plan::query()->updateOrCreate(
        ['slug' => 'growth'],
        [
            'name' => 'Growth',
            'stripe_price_id' => 'price_growth_123',
            'stripe_product_id' => 'prod_growth',
            'max_users' => 5,
            'max_items' => 500,
            'max_replies' => 2000,
        ]
    );

    Http::fake([
        'https://stripe.test/v1/customers' => Http::response(['id' => 'cus_test_123'], 200),
        'https://stripe.test/v1/checkout/sessions' => Http::response(['url' => 'https://checkout.stripe.test/session_abc'], 200),
    ]);

    $this->actingAs($owner)
        ->post(route('billing.checkout'), [
            'plan' => 'growth',
            'account_id' => $account->id,
        ])
        ->assertRedirect('https://checkout.stripe.test/session_abc');

    expect($account->fresh()->stripe_customer_id)->toBe('cus_test_123');

    Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($account): bool {
        if ($request->url() !== 'https://stripe.test/v1/checkout/sessions') {
            return false;
        }

        parse_str($request->body(), $payload);

        return ($payload['mode'] ?? null) === 'subscription'
            && ($payload['line_items'][0]['price'] ?? null) === 'price_growth_123'
            && ($payload['line_items'][0]['quantity'] ?? null) == 1
            && ($payload['metadata']['account_id'] ?? null) === $account->id
            && ($payload['metadata']['account_uuid'] ?? null) === $account->id
            && ($payload['metadata']['plan_slug'] ?? null) === 'growth'
            && ($payload['subscription_data']['metadata']['account_id'] ?? null) === $account->id
            && ($payload['subscription_data']['metadata']['account_uuid'] ?? null) === $account->id
            && ($payload['subscription_data']['metadata']['plan_slug'] ?? null) === 'growth'
            && str_contains((string) ($payload['success_url'] ?? ''), 'session_id={CHECKOUT_SESSION_ID}')
            && str_contains((string) ($payload['success_url'] ?? ''), 'plan=growth')
            && str_contains((string) ($payload['cancel_url'] ?? ''), 'plan=growth');
    });
});

test('checkout return urls are normalized to active host when config host differs', function () {
    config()->set('services.stripe.checkout_success_url', 'https://capture.example.com/billing/success');
    config()->set('services.stripe.checkout_cancel_url', 'https://capture.example.com/billing/cancel');

    $owner = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $owner->id]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Plan::query()->updateOrCreate(
        ['slug' => 'growth'],
        [
            'name' => 'Growth',
            'stripe_price_id' => 'price_growth_123',
            'stripe_product_id' => 'prod_growth',
            'max_users' => 5,
            'max_items' => 500,
            'max_replies' => 2000,
        ]
    );

    Http::fake([
        'https://stripe.test/v1/customers' => Http::response(['id' => 'cus_test_123'], 200),
        'https://stripe.test/v1/checkout/sessions' => Http::response(['url' => 'https://checkout.stripe.test/session_abc'], 200),
    ]);

    $this->actingAs($owner)
        ->post(route('billing.checkout'), [
            'plan' => 'growth',
            'account_id' => $account->id,
        ])
        ->assertRedirect('https://checkout.stripe.test/session_abc');

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        if ($request->url() !== 'https://stripe.test/v1/checkout/sessions') {
            return false;
        }

        parse_str($request->body(), $payload);

        return str_starts_with((string) ($payload['success_url'] ?? ''), 'http://localhost')
            && str_starts_with((string) ($payload['cancel_url'] ?? ''), 'http://localhost');
    });
});

test('billing success fallback syncs subscription id from checkout session', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create([
        'owner_user_id' => $owner->id,
        'stripe_customer_id' => null,
    ]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Plan::query()->updateOrCreate(
        ['slug' => 'growth'],
        [
            'name' => 'Growth',
            'stripe_price_id' => 'price_growth_123',
            'stripe_product_id' => 'prod_growth_123',
            'max_users' => 5,
            'max_items' => 500,
            'max_replies' => 2000,
        ]
    );

    $freePlan = Plan::query()->updateOrCreate(
        ['slug' => 'free'],
        [
            'name' => 'Free',
            'stripe_price_id' => null,
            'stripe_product_id' => null,
            'max_users' => 1,
            'max_items' => 100,
            'max_replies' => 500,
        ]
    );

    $proPlan = Plan::query()->updateOrCreate(
        ['slug' => 'pro'],
        [
            'name' => 'Pro',
            'stripe_price_id' => 'price_pro_123',
            'stripe_product_id' => 'prod_pro_123',
            'max_users' => 20,
            'max_items' => 100000,
            'max_replies' => 1000000,
        ]
    );

    $subscription = Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $freePlan->id,
        'stripe_subscription_id' => null,
        'status' => 'active',
    ]);

    Http::fake([
        'https://stripe.test/v1/checkout/sessions/cs_success_123' => Http::response([
            'id' => 'cs_success_123',
            'status' => 'complete',
            'payment_status' => 'paid',
            'customer' => 'cus_success_123',
            'subscription' => 'sub_success_123',
            'metadata' => [
                'account_uuid' => $account->id,
                'plan_slug' => 'pro',
            ],
        ], 200),
    ]);

    $this->actingAs($owner)
        ->withSession(['active_account_id' => $account->id])
        ->get(route('billing.success', ['session_id' => 'cs_success_123']))
        ->assertRedirect(route('manage.forms.index'));

    expect($account->fresh()->stripe_customer_id)->toBe('cus_success_123');
    expect($subscription->fresh()->stripe_subscription_id)->toBe('sub_success_123');
    expect($subscription->fresh()->plan_id)->toBe($proPlan->id);
});

test('non owner cannot start billing checkout', function () {
    $member = User::factory()->create();
    $account = Account::factory()->create();

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $member->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    Plan::query()->updateOrCreate(
        ['slug' => 'growth'],
        [
            'name' => 'Growth',
            'stripe_price_id' => 'price_growth_123',
            'stripe_product_id' => 'prod_growth',
            'max_users' => 5,
            'max_items' => 500,
            'max_replies' => 2000,
        ]
    );

    Http::fake();

    $this->actingAs($member)
        ->post(route('billing.checkout'), [
            'plan' => 'growth',
            'account_id' => $account->id,
        ])
        ->assertForbidden();

    Http::assertNothingSent();
});

test('owner can view middle plan page before checkout', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $owner->id]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('billing.plan.show', ['plan' => 'growth', 'account_id' => $account->id]))
        ->assertOk()
        ->assertSee('Choose your plan before checkout')
        ->assertSee('Growth')
        ->assertSee('Pro')
        ->assertSee('Continue to Stripe for Growth');
});

test('checkout failure returns with user-friendly validation error', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $owner->id]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Plan::query()->updateOrCreate(
        ['slug' => 'growth'],
        [
            'name' => 'Growth',
            'stripe_price_id' => 'price_growth_123',
            'stripe_product_id' => 'prod_growth',
            'max_users' => 5,
            'max_items' => 500,
            'max_replies' => 2000,
        ]
    );

    Http::fake([
        'https://stripe.test/v1/customers' => Http::response(['id' => 'cus_test_123'], 200),
        'https://stripe.test/v1/checkout/sessions' => Http::response([
            'error' => ['message' => 'No such price: price_growth_123'],
        ], 400),
    ]);

    $this->from('/manage/billing')
        ->actingAs($owner)
        ->post(route('billing.checkout'), [
            'plan' => 'growth',
            'account_id' => $account->id,
        ])
        ->assertRedirect('/manage/billing')
        ->assertSessionHasErrors([
            'plan' => 'Unable to start checkout right now. Please try again in a moment.',
        ]);
});

test('root post fallback starts billing checkout for paid plan', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $owner->id]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Plan::query()->updateOrCreate(
        ['slug' => 'growth'],
        [
            'name' => 'Growth',
            'stripe_price_id' => 'price_growth_123',
            'stripe_product_id' => 'prod_growth',
            'max_users' => 5,
            'max_items' => 500,
            'max_replies' => 2000,
        ]
    );

    Http::fake([
        'https://stripe.test/v1/customers' => Http::response(['id' => 'cus_test_123'], 200),
        'https://stripe.test/v1/checkout/sessions' => Http::response(['url' => 'https://checkout.stripe.test/session_abc'], 200),
    ]);

    $this->actingAs($owner)
        ->post('/', [
            'plan' => 'growth',
            'account_id' => $account->id,
        ])
        ->assertRedirect('https://checkout.stripe.test/session_abc');
});

test('checkout infers missing stripe ids from metadata and proceeds', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $owner->id]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $plan = Plan::query()->updateOrCreate(
        ['slug' => 'growth'],
        [
            'name' => 'Growth',
            'stripe_price_id' => null,
            'stripe_product_id' => null,
            'max_users' => 5,
            'max_items' => 500,
            'max_replies' => 2000,
        ]
    );

    Http::fake([
        'https://stripe.test/v1/prices*' => Http::response([
            'data' => [
                [
                    'id' => 'price_growth_123',
                    'type' => 'recurring',
                    'active' => true,
                    'metadata' => [
                        'app' => 'capture',
                        'tier' => 'growth',
                    ],
                    'product' => [
                        'id' => 'prod_growth_123',
                        'name' => 'Growth',
                        'metadata' => [
                            'app' => 'capture',
                            'tier' => 'growth',
                        ],
                    ],
                ],
            ],
            'has_more' => false,
        ], 200),
        'https://stripe.test/v1/customers' => Http::response(['id' => 'cus_test_123'], 200),
        'https://stripe.test/v1/checkout/sessions' => Http::response(['url' => 'https://checkout.stripe.test/session_abc'], 200),
    ]);

    $this->actingAs($owner)
        ->post(route('billing.checkout'), [
            'plan' => 'growth',
            'account_id' => $account->id,
        ])
        ->assertRedirect('https://checkout.stripe.test/session_abc');

    $plan->refresh();

    expect($plan->stripe_price_id)->toBe('price_growth_123')
        ->and($plan->stripe_product_id)->toBe('prod_growth_123');
});

test('owner can open billing portal', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->create([
        'owner_user_id' => $owner->id,
        'stripe_customer_id' => 'cus_existing_123',
    ]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Http::fake([
        'https://stripe.test/v1/billing_portal/sessions' => Http::response(['url' => 'https://billing.stripe.test/portal_abc'], 200),
    ]);

    $this->actingAs($owner)
        ->post(route('billing.portal'), [
            'account_id' => $account->id,
        ])
        ->assertRedirect('https://billing.stripe.test/portal_abc');
});
