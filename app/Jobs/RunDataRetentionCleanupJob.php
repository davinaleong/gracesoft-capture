<?php

namespace App\Jobs;

use App\Services\DataRetentionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunDataRetentionCleanupJob implements ShouldQueue
{
    use Queueable;

    public function handle(DataRetentionService $dataRetentionService): void
    {
        $dataRetentionService->cleanup();
    }
}
