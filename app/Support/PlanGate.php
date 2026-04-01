<?php

namespace App\Support;

use App\Models\Form;
use App\Models\Subscription;
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

    public function insightsEnabled(string $accountId): bool
    {
        if ((bool) config('capture.features.insights_force_enabled', false)) {
            return true;
        }

        $allowedPlans = (array) config('capture.features.insights_allowed_plans', ['pro']);

        return in_array($this->resolveAccountPlan($accountId), $allowedPlans, true);
    }

    public function formCreationAllowed(string $accountId): bool
    {
        if (! (bool) config('capture.features.plan_enforcement_enabled', true)) {
            return true;
        }

        $plan = $this->resolveAccountPlan($accountId);

        if ($plan !== 'starter') {
            return true;
        }

        $limit = max((int) config('capture.features.starter_form_limit', 1), 1);
        $current = Form::query()
            ->where('account_id', $accountId)
            ->count();

        return $current < $limit;
    }

    public function collaboratorInviteRoleAllowed(string $accountId, string $role): bool
    {
        if (! (bool) config('capture.features.plan_enforcement_enabled', true)) {
            return true;
        }

        $plan = $this->resolveAccountPlan($accountId);
        $roleLimits = (array) config('capture.features.plan_invite_roles', [
            'starter' => ['viewer'],
            'growth' => ['member', 'viewer'],
            'pro' => ['owner', 'member', 'viewer'],
        ]);

        $allowedRoles = $roleLimits[$plan] ?? ['member', 'viewer'];

        if (! is_array($allowedRoles)) {
            return false;
        }

        return in_array($role, $allowedRoles, true);
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
                $localPlan = Subscription::query()
                    ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                    ->where('subscriptions.account_id', $accountId)
                    ->orderByRaw("case when subscriptions.status in ('active', 'trialing', 'past_due') then 0 else 1 end")
                    ->orderByDesc('subscriptions.updated_at')
                    ->value('plans.slug');

                if (is_string($localPlan) && $localPlan !== '') {
                    return $localPlan;
                }

                return $this->hqService->fetchSubscriptionPlan($accountId)
                    ?? (string) config('capture.features.default_plan', 'growth');
            }
        );
    }
}
