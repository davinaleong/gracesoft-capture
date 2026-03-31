@props([
    'stages' => [],
    'title' => 'Funnel',
])

@php
    $maxCount = max(array_column($stages, 'count'));
    $safeMax = max($maxCount, 1);
@endphp

<div class="space-y-2">
    <h2 class="text-base font-semibold text-gs-black-800 mb-1">{{ $title }}</h2>

    @forelse ($stages as $stage)
        <div class="space-y-1">
            <div class="flex items-center justify-between text-xs text-gs-black-700">
                <span>{{ $stage['label'] }}</span>
                <span>{{ $stage['count'] }}</span>
            </div>
            <div class="h-3 rounded bg-gray-100 overflow-hidden">
                <div
                    class="h-3 rounded bg-gs-purple-500"
                    style="width: {{ (int) round(($stage['count'] / $safeMax) * 100) }}%"
                ></div>
            </div>
        </div>
    @empty
        <p class="text-sm text-gs-black-600">No funnel data available.</p>
    @endforelse
</div>
