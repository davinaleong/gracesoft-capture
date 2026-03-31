<?php

use App\Jobs\SyncFeedbackToHQJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    $payload = [
        'name' => 'Alyssa User',
        'email' => 'alyssa@example.com',
        'subject' => 'Need help with inbox',
        'message' => 'Could you help me with status transitions?',
        'account_id' => '3175f367-228d-4d4f-bd57-c34600a76fa1',
    ];

    $this->post(route('support.store'), $payload)
        ->assertRedirect(route('support.create'))
        ->assertSessionHas('status');

    Queue::assertPushed(SyncFeedbackToHQJob::class, function (SyncFeedbackToHQJob $job) use ($payload) {
        return data_get($job->feedbackPayload, 'name') === $payload['name']
            && data_get($job->feedbackPayload, 'email') === $payload['email']
            && data_get($job->feedbackPayload, 'subject') === $payload['subject']
            && data_get($job->feedbackPayload, 'message') === $payload['message']
            && data_get($job->feedbackPayload, 'account_id') === $payload['account_id'];
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
