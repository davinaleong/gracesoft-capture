<?php

use App\Models\AccountMembership;
use App\Models\Enquiry;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('viewer cannot update enquiry status when access enforcement is enabled', function () {
    config(['capture.features.enforce_access_context' => true]);

    $viewer = User::factory()->create();
    $form = Form::factory()->create([
        'account_id' => '3b8f0612-f58f-45d8-9a8e-cdb0f9cc5db2',
    ]);

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'status' => 'new',
    ]);

    AccountMembership::query()->create([
        'account_id' => $form->account_id,
        'user_id' => $viewer->id,
        'role' => 'viewer',
        'joined_at' => now(),
    ]);

    $this->actingAs($viewer)
        ->post(route('inbox.status.update', ['enquiry' => $enquiry, 'account_id' => $form->account_id]), [
            'status' => 'contacted',
        ])
        ->assertForbidden();
});

test('member can update enquiry status when access enforcement is enabled', function () {
    config(['capture.features.enforce_access_context' => true]);

    $member = User::factory()->create();
    $form = Form::factory()->create([
        'account_id' => '4ca8f64b-5fd2-4ebf-a036-64257ac146dc',
    ]);

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'status' => 'new',
    ]);

    AccountMembership::query()->create([
        'account_id' => $form->account_id,
        'user_id' => $member->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->post(route('inbox.status.update', ['enquiry' => $enquiry, 'account_id' => $form->account_id]), [
            'status' => 'contacted',
        ])
        ->assertRedirect(route('inbox.show', $enquiry));

    expect($enquiry->fresh()->status)->toBe('contacted');
});

test('viewer cannot create form when access enforcement is enabled', function () {
    config(['capture.features.enforce_access_context' => true]);

    $viewer = User::factory()->create();
    $accountId = '0eb6d6a7-dfff-4ec2-9d48-e5f6f65f99b2';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $viewer->id,
        'role' => 'viewer',
        'joined_at' => now(),
    ]);

    $this->actingAs($viewer)
        ->post(route('manage.forms.store', ['account_id' => $accountId]), [
            'name' => 'Blocked Form',
            'account_id' => $accountId,
            'application_id' => '55aeb676-c2ee-40ea-9d98-a4f3f1845cef',
        ])
        ->assertForbidden();
});
