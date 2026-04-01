<?php

use App\Jobs\SyncFeedbackToHQJob;
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
