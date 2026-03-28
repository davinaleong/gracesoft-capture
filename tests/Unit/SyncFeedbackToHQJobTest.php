<?php

use App\Jobs\SyncFeedbackToHQJob;
use App\Services\HQService;

it('passes payload to hq service when feedback job is handled', function () {
    $payload = [
        'name' => 'Alyssa User',
        'email' => 'alyssa@example.com',
        'subject' => 'Need help',
        'message' => 'Please help with setup.',
    ];

    $service = \Mockery::mock(HQService::class);
    $service->shouldReceive('sendFeedback')
        ->once()
        ->with($payload)
        ->andReturnTrue();

    $job = new SyncFeedbackToHQJob($payload);
    $job->handle($service);
});
