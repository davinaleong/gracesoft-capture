<?php

namespace App\Jobs;

use App\Services\HQService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncFeedbackToHQJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $feedbackPayload
     */
    public function __construct(public array $feedbackPayload)
    {
    }

    public function handle(HQService $hqService): void
    {
        $hqService->sendFeedback($this->feedbackPayload);
    }
}
