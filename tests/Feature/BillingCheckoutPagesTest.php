<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('billing success page is accessible', function () {
    $this->get(route('billing.success'))
        ->assertOk()
        ->assertSee('Payment successful');
});

test('billing cancel page is accessible', function () {
    $this->get(route('billing.cancel'))
        ->assertOk()
        ->assertSee('Checkout Cancelled');
});

test('billing cancel page shows retry checkout for authenticated users with plan', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->get(route('billing.cancel', ['plan' => 'growth']))
        ->assertOk()
        ->assertSee('Retry checkout for GROWTH plan.')
        ->assertSee('Retry checkout');
});
