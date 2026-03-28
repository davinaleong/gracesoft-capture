<?php

use App\Models\Enquiry;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inbox list page shows enquiry rows', function () {
    $form = Form::factory()->create(['name' => 'Support Form']);

    Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Need support',
        'status' => 'new',
    ]);

    $this->get(route('inbox.index'))
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertSee('Need support');
});

test('inbox detail page shows full enquiry content', function () {
    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'subject' => 'Pricing question',
        'message' => 'Can you share the pro plan details?',
    ]);

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Pricing question')
        ->assertSee('pro plan details');
});

test('status transitions from new to contacted and sets contacted_at', function () {
    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'status' => 'new',
        'contacted_at' => null,
    ]);

    $this->post(route('inbox.status.update', $enquiry), [
        'status' => 'contacted',
    ])->assertRedirect(route('inbox.show', $enquiry));

    $enquiry->refresh();

    expect($enquiry->status)->toBe('contacted');
    expect($enquiry->contacted_at)->not->toBeNull();
});

test('status transitions from contacted to closed and sets closed_at', function () {
    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'status' => 'contacted',
        'closed_at' => null,
    ]);

    $this->post(route('inbox.status.update', $enquiry), [
        'status' => 'closed',
    ])->assertRedirect(route('inbox.show', $enquiry));

    $enquiry->refresh();

    expect($enquiry->status)->toBe('closed');
    expect($enquiry->closed_at)->not->toBeNull();
});

test('invalid transition from new directly to closed is rejected', function () {
    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'status' => 'new',
    ]);

    $this->from(route('inbox.show', $enquiry))
        ->post(route('inbox.status.update', $enquiry), [
            'status' => 'closed',
        ])
        ->assertRedirect(route('inbox.show', $enquiry))
        ->assertSessionHasErrors('status');

    expect($enquiry->fresh()->status)->toBe('new');
});
