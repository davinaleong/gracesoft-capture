<?php

use App\Jobs\SyncFeedbackToHQJob;
use App\Models\AccountMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('contact support page can be opened', function () {
    $this->get(route('support.create'))
        ->assertOk()
    ->assertSee('Contact Support')
    ->assertSee('Open Support Form')
    ->assertSee('support-feedback-modal');
});

test('submitting support form dispatches feedback sync job', function () {
    Queue::fake();
    Mail::shouldReceive('raw')
        ->once();

    $payload = [
        'name' => 'Alyssa User',
        'email' => 'alyssa@example.com',
        'subject' => 'technical_issue',
        'message' => 'Could you help me with status transitions?',
    ];

    $this->post(route('support.store'), $payload)
        ->assertRedirect(route('support.create'))
        ->assertSessionHas('status');

    Queue::assertPushed(SyncFeedbackToHQJob::class, function (SyncFeedbackToHQJob $job) use ($payload) {
        return data_get($job->feedbackPayload, 'name') === $payload['name']
            && data_get($job->feedbackPayload, 'email') === $payload['email']
            && data_get($job->feedbackPayload, 'subject') === 'Technical issue'
            && data_get($job->feedbackPayload, 'message') === $payload['message']
            && data_get($job->feedbackPayload, 'account_id') === null;
    });
});

test('support form validates required fields', function () {
    Queue::fake();

    $this->from(route('support.create'))
        ->post(route('support.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => '',
            'message' => '',
        ])
        ->assertRedirect(route('support.create'))
        ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

    Queue::assertNothingPushed();
});

test('panel support page requires authenticated user context', function () {
    $this->get(route('panel.support.create'))
        ->assertRedirect(route('login'));
});

test('authenticated users can open panel support page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('panel.support.create'))
        ->assertOk()
        ->assertSee('Contact Support')
        ->assertSee(route('panel.support.store'), false);
});

test('panel support submission redirects back to panel support route', function () {
    Queue::fake();
    Mail::shouldReceive('raw')->once();

    $user = User::factory()->create();

    AccountMembership::query()->create([
        'account_id' => 'aaaaaaaa-1111-1111-1111-111111111111',
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $payload = [
        'name' => 'Panel User',
        'email' => 'panel.user@example.com',
        'subject' => 'technical_issue',
        'message' => 'Panel-scoped support request.',
    ];

    $this->actingAs($user)
        ->post(route('panel.support.store'), $payload)
        ->assertRedirect(route('panel.support.create'))
        ->assertSessionHas('status');

    Queue::assertPushed(SyncFeedbackToHQJob::class, function (SyncFeedbackToHQJob $job) {
        return data_get($job->feedbackPayload, 'account_id') === 'aaaaaaaa-1111-1111-1111-111111111111';
    });
});
