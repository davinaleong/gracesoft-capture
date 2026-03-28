<?php

namespace App\Support;

use App\Services\HQService;
use Illuminate\Support\Facades\Cache;

class PlanGate
{
    public function __construct(private readonly HQService $hqService)
    {
    }

    public function notesEnabled(string $accountId): bool
    {
        if ((bool) config('capture.features.notes_force_enabled', false)) {
            return true;
        }

        $plan = Cache::remember(
            'capture:plan:' . $accountId,
            now()->addSeconds((int) config('capture.features.plan_cache_ttl_seconds', 300)),
            function () use ($accountId): string {
                return $this->hqService->fetchSubscriptionPlan($accountId)
                    ?? (string) config('capture.features.default_plan', 'growth');
            }
        );

        return in_array($plan, ['pro'], true);
    }
}
