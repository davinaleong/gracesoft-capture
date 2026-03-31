<?php

use App\Models\AccountMembership;
use App\Models\Enquiry;
use App\Models\Form;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can add reply to enquiry when access context is enforced', function () {
    config()->set('capture.features.enforce_access_context', true);

    $owner = User::factory()->create();
    $accountId = '4ae8a394-3339-4f90-9071-78eff1965d4d';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $form = Form::factory()->create([
        'account_id' => $accountId,
        'application_id' => '271f95cb-b4f8-4c20-a39e-6388d7351c58',
    ]);

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $accountId,
        'application_id' => $form->application_id,
    ]);

    $this->actingAs($owner)
        ->post(route('inbox.replies.store', ['enquiry' => $enquiry, 'account_id' => $accountId]), [
            'content' => 'We will contact you shortly.',
            'is_internal' => '1',
        ])
        ->assertRedirect(route('inbox.show', $enquiry));

    $reply = Reply::query()->first();

    expect($reply)->not->toBeNull();
    expect($reply->content)->toBe('We will contact you shortly.');
    expect($reply->sender_type)->toBe('user');
    expect($reply->is_internal)->toBeTrue();
});

test('viewer cannot add reply to enquiry when access context is enforced', function () {
    config()->set('capture.features.enforce_access_context', true);

    $viewer = User::factory()->create();
    $accountId = '26c11af5-77ef-4979-b6d5-b71a1bf4f5eb';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $viewer->id,
        'role' => 'viewer',
        'joined_at' => now(),
    ]);

    $form = Form::factory()->create([
        'account_id' => $accountId,
    ]);

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $accountId,
        'application_id' => $form->application_id,
    ]);

    $this->actingAs($viewer)
        ->post(route('inbox.replies.store', ['enquiry' => $enquiry, 'account_id' => $accountId]), [
            'content' => 'Blocked viewer reply',
        ])
        ->assertForbidden();

    expect(Reply::query()->count())->toBe(0);
});

test('inbox detail displays existing replies', function () {
    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    Reply::query()->create([
        'enquiry_id' => $enquiry->id,
        'account_id' => $enquiry->account_id,
        'sender_type' => 'user',
        'sender_id' => null,
        'email' => null,
        'content' => 'First reply content',
        'is_internal' => true,
        'metadata' => ['user_id' => 1],
    ]);

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Replies')
        ->assertSee('First reply content')
        ->assertSee('Internal');
});
