<?php

use App\Models\Account;
use App\Models\AccountMembership;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration provisions workspace membership and free subscription', function () {
    Plan::query()->firstOrCreate(
        ['slug' => 'free'],
        [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Free',
            'stripe_price_id' => null,
            'stripe_product_id' => null,
            'max_users' => 1,
            'max_items' => 50,
            'max_replies' => 100,
        ]
    );

    $this->post(route('register.store'), [
        'name' => 'Billing User',
        'email' => 'billing.user@example.com',
        'password' => 'strong-pass-123',
        'password_confirmation' => 'strong-pass-123',
    ])->assertRedirect(route('verification.notice'));

    $account = Account::query()->first();

    expect($account)->not->toBeNull();
    expect($account->owner->email)->toBe('billing.user@example.com');

    expect(AccountMembership::query()->where('account_id', $account->id)->where('role', 'owner')->exists())->toBeTrue();
    expect(Subscription::query()->where('account_id', $account->id)->where('status', 'active')->exists())->toBeTrue();
});
