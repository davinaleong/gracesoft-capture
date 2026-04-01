<?php

use App\Models\Account;
use App\Models\AccountMembership;
use App\Models\Plan;
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
