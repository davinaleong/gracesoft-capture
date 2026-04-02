<?php

use App\Models\AccountMembership;
use App\Models\Account;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('authenticated user can open security settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->get(route('settings.security.index'))
        ->assertOk()
        ->assertSee('Account Security')
    ->assertSee($user->email)
        ->assertSee('Two-Factor Authentication');
});

test('user can change password with current password', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->actingAs($user, 'web')
        ->from(route('settings.security.index'))
        ->put(route('settings.security.password.update'), [
            'current_password' => 'password',
            'password' => 'new-secure-pass-123',
            'password_confirmation' => 'new-secure-pass-123',
        ])
        ->assertRedirect(route('settings.security.index'))
        ->assertSessionHasNoErrors();

    expect(Hash::check('new-secure-pass-123', (string) $user->fresh()->password))->toBeTrue();
});

test('password change fails with invalid current password', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->actingAs($user, 'web')
        ->from(route('settings.security.index'))
        ->put(route('settings.security.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secure-pass-123',
            'password_confirmation' => 'new-secure-pass-123',
        ])
        ->assertRedirect(route('settings.security.index'))
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', (string) $user->fresh()->password))->toBeTrue();
});

test('user can enable and disable two factor authentication', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->post(route('settings.security.two-factor.toggle'), [
            'enabled' => 1,
        ])
        ->assertRedirect();

    expect($user->fresh()->two_factor_enabled_at)->not->toBeNull();

    $this->actingAs($user, 'web')
        ->post(route('settings.security.two-factor.toggle'), [
            'enabled' => 0,
        ])
        ->assertRedirect();

    expect($user->fresh()->two_factor_enabled_at)->toBeNull();
});

test('user session email links to security settings page', function () {
    $user = User::factory()->create();
    $accountId = '8499719d-0d93-4df8-80c9-4ded40f1836e';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $this->actingAs($user, 'web')
        ->get(route('collaborators.index', ['account_id' => $accountId]))
        ->assertOk()
        ->assertSee(route('settings.security.index'));
});

test('security settings page shows subscription info for active workspace', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $user->id]);
    $plan = Plan::query()->updateOrCreate([
        'slug' => 'growth',
    ], [
        'name' => 'Growth',
        'stripe_price_id' => null,
        'stripe_product_id' => null,
        'max_users' => 5,
        'max_items' => 500,
        'max_replies' => 2000,
    ]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $this->actingAs($user, 'web')
        ->withSession(['active_account_id' => $account->id])
        ->get(route('settings.security.index'))
        ->assertOk()
        ->assertSee('Subscription')
        ->assertSee('Growth')
        ->assertSee('active');
});
