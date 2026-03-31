<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HQService
{
    public function validateApplication(string $accountId, string $applicationId): bool
    {
        if (! (bool) config('hq.validation.enabled', false)) {
            return true;
        }

        if (! (bool) config('hq.enabled', true)) {
            return true;
        }

        $url = (string) config('hq.validation.url', '');

        if ($url === '') {
            return true;
        }

        $cacheKey = sprintf('capture:hq:application:%s:%s', $accountId, $applicationId);
        $ttlSeconds = max((int) config('hq.validation.cache_ttl_seconds', 120), 1);

        return Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), function () use ($url, $accountId, $applicationId): bool {
            try {
                $response = $this->baseRequest()->post($url, [
                    'account_id' => $accountId,
                    'application_id' => $applicationId,
                ]);

                if (! $response->successful()) {
                    return false;
                }

                return $this->extractApplicationValidationResult((array) $response->json());
            } catch (Throwable $exception) {
                Log::warning('Failed to validate application against HQ.', [
                    'account_id' => $accountId,
                    'application_id' => $applicationId,
                    'error' => $exception->getMessage(),
                ]);

                return false;
            }
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function sendAnalyticsEvent(array $payload): bool
    {
        return $this->postToSyncEndpoint(
            (string) config('hq.sync.analytics_url', ''),
            $payload,
            'analytics'
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function sendFeedback(array $payload): bool
    {
        return $this->postToSyncEndpoint(
            (string) config('hq.sync.feedback_url', ''),
            $payload,
            'feedback'
        );
    }

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
            $response = $this->baseRequest()
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

    /**
     * @param array<string, mixed> $payload
     */
    private function extractApplicationValidationResult(array $payload): bool
    {
        $candidates = [
            data_get($payload, 'valid'),
            data_get($payload, 'data.valid'),
            data_get($payload, 'is_valid'),
            data_get($payload, 'data.is_valid'),
            data_get($payload, 'allowed'),
            data_get($payload, 'data.allowed'),
        ];

        foreach ($candidates as $candidate) {
            if (is_bool($candidate)) {
                return $candidate;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postToSyncEndpoint(string $url, array $payload, string $syncType): bool
    {
        if (! (bool) config('hq.enabled', true)) {
            return false;
        }

        if ($url === '') {
            return false;
        }

        try {
            $response = $this->baseRequest()->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('HQ sync request failed.', [
                    'sync_type' => $syncType,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            Log::warning('HQ sync request threw an exception.', [
                'sync_type' => $syncType,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function baseRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->withHeaders([
                'X-App-Id' => (string) config('hq.credentials.app_id', ''),
                'X-App-Key' => (string) config('hq.credentials.app_key', ''),
                'X-App-Secret' => (string) config('hq.credentials.app_secret', ''),
            ])
            ->retry(
                (int) config('hq.http.retry_times', 1),
                (int) config('hq.http.retry_sleep_milliseconds', 100)
            )
            ->timeout((int) config('hq.http.timeout_seconds', 5));
    }
}
