<?php

use App\Models\AccountMembership;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('integration page shows account scoped forms and embed snippets', function () {
    config()->set('capture.features.enforce_access_context', true);

    $accountId = 'aaaaaaaa-1111-1111-1111-111111111111';
    $otherAccountId = 'bbbbbbbb-2222-2222-2222-222222222222';

    $user = User::factory()->create();

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $user->id,
        'role' => 'viewer',
        'joined_at' => now(),
    ]);

    $inScopeForm = Form::factory()->create([
        'name' => 'Support Form',
        'account_id' => $accountId,
        'public_token' => 'frm_in_scope_token_123',
    ]);

    Form::factory()->create([
        'name' => 'Other Account Form',
        'account_id' => $otherAccountId,
        'public_token' => 'frm_other_scope_token_456',
    ]);

    $this->actingAs($user)
        ->get(route('integrations.index', ['account_id' => $accountId]))
        ->assertOk()
        ->assertSee('Integration')
        ->assertSee('Support Form')
        ->assertDontSee('Other Account Form')
        ->assertSee(route('forms.show', $inScopeForm->public_token), false)
        ->assertSee('Send Test Enquiry');
});

test('integration page can focus on a specific form from form actions', function () {
    config()->set('capture.features.enforce_access_context', true);

    $accountId = 'cccccccc-3333-3333-3333-333333333333';

    $user = User::factory()->create();

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $user->id,
        'role' => 'viewer',
        'joined_at' => now(),
    ]);

    $focusedForm = Form::factory()->create([
        'name' => 'Focused Form',
        'account_id' => $accountId,
    ]);

    Form::factory()->create([
        'name' => 'Sibling Form',
        'account_id' => $accountId,
    ]);

    $this->actingAs($user)
        ->get(route('integrations.index', [
            'account_id' => $accountId,
            'form_id' => $focusedForm->id,
        ]))
        ->assertOk()
        ->assertSee('Showing embed code for the selected form.')
        ->assertSee('Focused Form')
        ->assertDontSee('Sibling Form');
});
