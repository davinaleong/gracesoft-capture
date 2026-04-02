<?php

use App\Models\Enquiry;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

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

test('inbox list page can filter by search query', function () {
    $form = Form::factory()->create(['name' => 'Support Form']);

    Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'name' => 'Alice Searchmatch',
        'email' => 'alice@example.com',
        'subject' => 'Need migration help',
        'status' => 'new',
    ]);

    Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'name' => 'Bob Different',
        'email' => 'bob@example.com',
        'subject' => 'Billing issue',
        'status' => 'new',
    ]);

    $this->get(route('inbox.index', ['search' => 'migration']))
        ->assertOk()
        ->assertSee('Alice Searchmatch')
        ->assertDontSee('Bob Different');
});

test('inbox list shows account context badge when account is selected', function () {
    $form = Form::factory()->create([
        'account_id' => 'e9d203ef-1000-4902-bb37-646e65bf1ff1',
    ]);

    Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'name' => 'Context User',
    ]);

    $this->get(route('inbox.index', ['account_id' => $form->account_id]))
        ->assertOk()
        ->assertSee('Context User');
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

test('timeline shows latest reply after reply submission', function () {
    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'status' => 'new',
        'contacted_at' => null,
    ]);

    $this->post(route('inbox.replies.store', $enquiry), [
        'content' => 'Reply that should appear in timeline updates.',
    ])->assertRedirect(route('inbox.show', $enquiry));

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Latest reply')
        ->assertDontSee('No replies yet');
});

test('timeline shows latest note after note creation', function () {
    config()->set('capture.features.notes_force_enabled', true);

    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    $this->post(route('inbox.notes.store', $enquiry), [
        'content' => 'Timeline should include this note event.',
    ])->assertRedirect(route('inbox.show', $enquiry));

    $this->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertSee('Latest note')
        ->assertDontSee('No notes yet');
});
