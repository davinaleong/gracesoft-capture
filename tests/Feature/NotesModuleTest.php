<?php

use App\Models\Enquiry;
use App\Models\Form;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inbox detail shows notes upgrade message when notes are not enabled', function () {
    config()->set('capture.features.notes_force_enabled', false);
    config()->set('capture.features.default_plan', 'growth');

    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Notes are available on the Pro plan only.');
});

test('note can be added from inbox detail when notes are enabled', function () {
    config()->set('capture.features.notes_force_enabled', true);

    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    $payload = [
        'user_id' => '6f7ac81e-0a80-45e6-aa1e-419801caad52',
        'content' => 'Follow-up requested by customer via call.',
    ];

    $this->post(route('inbox.notes.store', $enquiry), $payload)
        ->assertRedirect(route('inbox.show', $enquiry))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('notes', [
        'enquiry_id' => $enquiry->id,
        'user_id' => $payload['user_id'],
        'content' => $payload['content'],
    ]);

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Follow-up requested by customer via call.');
});

test('note creation is blocked when notes are not enabled', function () {
    config()->set('capture.features.notes_force_enabled', false);
    config()->set('capture.features.default_plan', 'growth');

    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    $this->from(route('inbox.show', $enquiry))
        ->post(route('inbox.notes.store', $enquiry), [
            'user_id' => '6f7ac81e-0a80-45e6-aa1e-419801caad52',
            'content' => 'This should not be stored.',
        ])
        ->assertRedirect(route('inbox.show', $enquiry))
        ->assertSessionHasErrors('notes');

    $this->assertDatabaseCount('notes', 0);
});

test('existing notes are listed on inbox detail', function () {
    config()->set('capture.features.notes_force_enabled', true);

    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    Note::factory()->create([
        'enquiry_id' => $enquiry->id,
        'user_id' => 'a790f860-a68d-4943-8f6e-b33f2fa668db',
        'content' => 'Customer requested callback tomorrow morning.',
    ]);

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Customer requested callback tomorrow morning.');
});
