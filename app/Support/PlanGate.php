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

        return in_array($this->resolveAccountPlan($accountId), ['pro'], true);
    }

    public function complianceViewsEnabled(?string $accountId): bool
    {
        if (! (bool) config('capture.features.admin_compliance_plan_gate_enabled', false)) {
            return true;
        }

        if (! is_string($accountId) || $accountId === '') {
            return true;
        }

        $allowedPlans = (array) config('capture.features.admin_compliance_allowed_plans', ['pro']);

        return in_array($this->resolveAccountPlan($accountId), $allowedPlans, true);
    }

    private function resolveAccountPlan(string $accountId): string
    {
        if ($accountId === '') {
            return (string) config('capture.features.default_plan', 'growth');
        }

        return Cache::remember(
            'capture:plan:' . $accountId,
            now()->addSeconds((int) config('capture.features.plan_cache_ttl_seconds', 300)),
            function () use ($accountId): string {
                return $this->hqService->fetchSubscriptionPlan($accountId)
                    ?? (string) config('capture.features.default_plan', 'growth');
            }
        );
    }
}
