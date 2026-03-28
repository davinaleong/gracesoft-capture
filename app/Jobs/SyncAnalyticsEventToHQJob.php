<?php

namespace App\Jobs;

use App\Services\HQService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncAnalyticsEventToHQJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $eventPayload
     */
    public function __construct(public array $eventPayload)
    {
    }

    public function handle(HQService $hqService): void
    {
        $hqService->sendAnalyticsEvent($this->eventPayload);
    }
}
