<?php

namespace App\Support;

use App\Models\AccountMembership;
use App\Models\Enquiry;
use App\Models\Form;

class GuidedTour
{
    public function __construct(private readonly PlanGate $planGate)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(?string $accountId, string $currentStep): array
    {
        $resolvedAccountId = is_string($accountId) ? trim($accountId) : '';
        $isGlobalScope = $resolvedAccountId === '';
        $formsCount = $this->formsCount($resolvedAccountId);
        $activeFormsCount = $this->activeFormsCount($resolvedAccountId);
        $enquiriesCount = $this->enquiriesCount($resolvedAccountId);
        $collaboratorCount = $this->collaboratorCount($resolvedAccountId);

        $planSlug = $this->planGate->planForAccount($resolvedAccountId !== '' ? $resolvedAccountId : null);
        $insightsEnabled = $isGlobalScope
            ? in_array($planSlug, (array) config('capture.features.insights_allowed_plans', ['pro']), true)
            : $this->planGate->insightsEnabled($resolvedAccountId);

        $steps = [
            [
                'key' => 'forms',
                'title' => 'Create your first form',
                'description' => 'Set up the form your visitors will submit from your website.',
                'complete' => $formsCount > 0,
                'available' => true,
                'href' => route('manage.forms.create'),
                'cta' => 'Create Form',
            ],
            [
                'key' => 'integrations',
                'title' => 'Publish the embed snippet',
                'description' => 'Copy the iframe code and add it to your site to start capturing enquiries.',
                'complete' => $enquiriesCount > 0,
                'available' => $formsCount > 0,
                'href' => route('integrations.index'),
                'cta' => 'Open Integrations',
            ],
            [
                'key' => 'inbox',
                'title' => 'Receive your first enquiry',
                'description' => 'Incoming submissions appear in Inbox where your team can reply and close the loop.',
                'complete' => $enquiriesCount > 0,
                'available' => $activeFormsCount > 0,
                'href' => route('inbox.index'),
                'cta' => 'Open Inbox',
            ],
            [
                'key' => 'insights',
                'title' => 'Review trends in Insights',
                'description' => $insightsEnabled
                    ? 'Track volume, conversion, and response time after enquiry data is available.'
                    : 'Insights is available on Pro plans. Upgrade to unlock this step.',
                'complete' => $insightsEnabled && $enquiriesCount > 0,
                'available' => $insightsEnabled,
                'href' => $insightsEnabled
                    ? route('insights.index')
                    : route('manage.forms.index', ['upgrade' => 'pro']),
                'cta' => $insightsEnabled ? 'Open Insights' : 'Upgrade to Pro',
            ],
        ];

        $nextStep = collect($steps)->first(function (array $step): bool {
            return ! $step['complete'];
        });

        return [
            'currentStep' => $currentStep,
            'planSlug' => $planSlug,
            'formsCount' => $formsCount,
            'activeFormsCount' => $activeFormsCount,
            'enquiriesCount' => $enquiriesCount,
            'collaboratorCount' => $collaboratorCount,
            'steps' => $steps,
            'nextStep' => is_array($nextStep) ? $nextStep : null,
        ];
    }

    private function formsCount(string $accountId): int
    {
        return Form::query()
            ->when($accountId !== '', fn ($query) => $query->where('account_id', $accountId))
            ->count();
    }

    private function activeFormsCount(string $accountId): int
    {
        return Form::query()
            ->when($accountId !== '', fn ($query) => $query->where('account_id', $accountId))
            ->where('is_active', true)
            ->count();
    }

    private function enquiriesCount(string $accountId): int
    {
        return Enquiry::query()
            ->when($accountId !== '', fn ($query) => $query->where('account_id', $accountId))
            ->count();
    }

    private function collaboratorCount(string $accountId): int
    {
        if ($accountId === '') {
            return 0;
        }

        return AccountMembership::query()
            ->where('account_id', $accountId)
            ->whereNull('removed_at')
            ->count();
    }
}
