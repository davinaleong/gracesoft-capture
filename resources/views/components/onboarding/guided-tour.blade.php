@props([
    'tour' => [],
    'title' => 'Guided setup tour',
])

@php
    $steps = is_array($tour) ? ($tour['steps'] ?? []) : [];
    $nextStep = is_array($tour) ? ($tour['nextStep'] ?? null) : null;
    $planSlug = strtoupper((string) (is_array($tour) ? ($tour['planSlug'] ?? 'growth') : 'growth'));
    $currentStep = (string) (is_array($tour) ? ($tour['currentStep'] ?? '') : '');
@endphp

<div {{ $attributes->class(['rounded-xl border border-gs-purple-200 bg-gs-purple-50 p-4']) }}>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="text-sm font-semibold text-gs-black-900">{{ $title }}</p>
            <p class="text-xs text-gs-black-700">Plan-aware onboarding for your workspace.</p>
        </div>
        <x-ui.badge variant="neutral">Plan: {{ $planSlug }}</x-ui.badge>
    </div>

    <div class="space-y-2">
        @foreach ($steps as $step)
            @php
                $isCurrent = ($step['key'] ?? null) === $currentStep;
                $isComplete = (bool) ($step['complete'] ?? false);
                $isAvailable = (bool) ($step['available'] ?? false);
            @endphp
            <div class="rounded border px-3 py-2 {{ $isCurrent ? 'border-gs-purple-300 bg-white' : 'border-gs-black-200 bg-white/80' }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gs-black-900">{{ $step['title'] ?? 'Step' }}</p>
                        <p class="text-xs text-gs-black-700">{{ $step['description'] ?? '' }}</p>
                    </div>
                    <x-ui.badge :variant="$isComplete ? 'success' : ($isAvailable ? 'info' : 'neutral')">
                        {{ $isComplete ? 'Completed' : ($isAvailable ? 'Next' : 'Locked') }}
                    </x-ui.badge>
                </div>
            </div>
        @endforeach
    </div>

    @if (is_array($nextStep) && is_string($nextStep['href'] ?? null) && ($nextStep['href'] ?? '') !== '')
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <x-ui.button tag="a" href="{{ $nextStep['href'] }}" size="sm">{{ $nextStep['cta'] ?? 'Continue Setup' }}</x-ui.button>
        </div>
    @endif
</div>
