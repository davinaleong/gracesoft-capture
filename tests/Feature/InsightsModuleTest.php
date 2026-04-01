<?php

use App\Models\AccountMembership;
use App\Models\Enquiry;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('insights page renders account scoped metrics', function () {
    config()->set('capture.features.enforce_access_context', true);
    config()->set('capture.features.default_plan', 'pro');

    $accountId = '11111111-1111-1111-1111-111111111111';
    $otherAccountId = '22222222-2222-2222-2222-222222222222';

    $user = User::factory()->create();

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $form = Form::factory()->create([
        'account_id' => $accountId,
        'application_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
    ]);

    $otherForm = Form::factory()->create([
        'account_id' => $otherAccountId,
        'application_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
    ]);

    Enquiry::factory()->count(3)->create([
        'form_id' => $form->id,
        'account_id' => $accountId,
        'application_id' => $form->application_id,
        'status' => 'new',
    ]);

    Enquiry::factory()->create([
        'form_id' => $otherForm->id,
        'account_id' => $otherAccountId,
        'application_id' => $otherForm->application_id,
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->get(route('insights.index', ['account_id' => $accountId, 'days' => 7]))
        ->assertOk()
        ->assertSee('Insights')
        ->assertSee('Workspace insights overview')
        ->assertSee('Total enquiries')
        ->assertSee('Conversion funnel')
        ->assertSee('3');
});

test('insights are blocked for non pro plans', function () {
    config()->set('capture.features.enforce_access_context', true);
    config()->set('capture.features.default_plan', 'starter');

    $accountId = '33333333-3333-3333-3333-333333333333';

    $user = User::factory()->create();

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('insights.index', ['account_id' => $accountId]))
        ->assertForbidden();
});
