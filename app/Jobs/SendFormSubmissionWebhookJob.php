<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFormSubmissionWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $webhookUrl,
        public array $payload
    ) {
    }

    public function handle(): void
    {
        try {
            $response = Http::acceptJson()->post($this->webhookUrl, $this->payload);

            if (! $response->successful()) {
                Log::warning('Form submission webhook failed.', [
                    'url' => $this->webhookUrl,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Form submission webhook threw exception.', [
                'url' => $this->webhookUrl,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
