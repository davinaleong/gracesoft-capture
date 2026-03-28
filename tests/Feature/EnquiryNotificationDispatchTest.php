<?php

use App\Jobs\SendEnquiryNotificationJob;
use App\Jobs\SyncAnalyticsEventToHQJob;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('submitting public form dispatches queued notification job', function () {
    Queue::fake();

    $form = Form::factory()->create([
        'settings' => [
            'notification_email' => 'notify@example.com',
        ],
    ]);

    $response = $this->post(route('forms.submit', $form->public_token), [
        'name' => 'Queue Tester',
        'email' => 'queue@example.com',
        'subject' => 'Queue this',
        'message' => 'Please enqueue notification.',
        'website' => '',
    ]);

    $response->assertRedirect(route('forms.show', $form->public_token));

    Queue::assertPushed(SendEnquiryNotificationJob::class, function (SendEnquiryNotificationJob $job) {
        return $job->recipientEmail === 'notify@example.com';
    });

    Queue::assertPushed(SyncAnalyticsEventToHQJob::class, function (SyncAnalyticsEventToHQJob $job) use ($form) {
        return data_get($job->eventPayload, 'event') === 'enquiry.created'
            && data_get($job->eventPayload, 'account_id') === $form->account_id
            && data_get($job->eventPayload, 'application_id') === $form->application_id;
    });
});
