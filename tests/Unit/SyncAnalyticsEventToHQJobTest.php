<?php

use App\Jobs\SyncAnalyticsEventToHQJob;
use App\Services\HQService;

it('passes payload to hq service when analytics job is handled', function () {
    $payload = [
        'event' => 'enquiry.created',
        'account_id' => '420f047b-b24d-4f4f-96da-5f1de55db0cc',
    ];

    $service = \Mockery::mock(HQService::class);
    $service->shouldReceive('sendAnalyticsEvent')
        ->once()
        ->with($payload)
        ->andReturnTrue();

    $job = new SyncAnalyticsEventToHQJob($payload);
    $job->handle($service);
});
