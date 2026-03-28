<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HQService
{
    public function fetchSubscriptionPlan(string $accountId): ?string
    {
        if (! (bool) config('hq.enabled', true)) {
            return null;
        }

        $url = (string) config('hq.sync.subscription_url', '');

        if ($url === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-App-Id' => (string) config('hq.credentials.app_id', ''),
                    'X-App-Key' => (string) config('hq.credentials.app_key', ''),
                    'X-App-Secret' => (string) config('hq.credentials.app_secret', ''),
                ])
                ->retry(
                    (int) config('hq.http.retry_times', 1),
                    (int) config('hq.http.retry_sleep_milliseconds', 100)
                )
                ->timeout((int) config('hq.http.timeout_seconds', 5))
                ->get($url, [
                    'account_id' => $accountId,
                ]);

            if (! $response->successful()) {
                return null;
            }

            return $this->extractPlan((array) $response->json());
        } catch (Throwable $exception) {
            Log::warning('Failed to fetch HQ subscription plan.', [
                'account_id' => $accountId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractPlan(array $payload): ?string
    {
        $candidates = [
            data_get($payload, 'plan'),
            data_get($payload, 'data.plan'),
            data_get($payload, 'subscription.plan'),
            data_get($payload, 'data.subscription.plan'),
            data_get($payload, 'data.tier'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return strtolower(trim($candidate));
            }
        }

        return null;
    }
}
