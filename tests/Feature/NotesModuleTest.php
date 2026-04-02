<?php

use App\Models\Enquiry;
use App\Models\Form;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

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

    $note = Note::query()->latest('id')->firstOrFail();

    expect($note->enquiry_id)->toBe($enquiry->id);
    expect($note->user_id)->toBe($payload['user_id']);
    expect($note->content)->toBe($payload['content']);

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Follow-up requested by customer via call.')
        ->assertSee('Created by:')
        ->assertDontSee($payload['user_id']);
});

test('note can be added without user id field input when notes are enabled', function () {
    config()->set('capture.features.notes_force_enabled', true);

    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    $this->post(route('inbox.notes.store', $enquiry), [
        'content' => 'Internal note without manual HQ user id entry.',
    ])->assertRedirect(route('inbox.show', $enquiry));

    $note = Note::query()->latest('id')->firstOrFail();

    expect(Str::isUuid((string) $note->user_id))->toBeTrue();
});

test('note metadata can be stored and rendered on inbox detail', function () {
    config()->set('capture.features.notes_force_enabled', true);

    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    $this->post(route('inbox.notes.store', $enquiry), [
        'user_id' => 'f61e75f2-7305-4f0c-a15f-041c560917ac',
        'content' => 'Escalated to specialist team.',
        'visibility' => 'external',
        'is_pinned' => '1',
        'tags' => 'priority, escalation',
        'reminder_at' => '2026-04-03',
    ])->assertRedirect(route('inbox.show', $enquiry));

    $note = Note::query()->first();

    expect($note)->not->toBeNull();
    expect($note->visibility)->toBe('external');
    expect($note->is_pinned)->toBeTrue();
    expect($note->tags)->toBe(['priority', 'escalation']);
    expect($note->reminder_at?->format('Y-m-d'))->toBe('2026-04-03');

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Pinned Notes')
        ->assertSee('External')
        ->assertSee('#priority')
        ->assertSee('Reminder: 2026-04-03');
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
