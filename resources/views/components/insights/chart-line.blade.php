@props([
    'points' => [],
    'title' => 'Trend',
])

@php
    $maxCount = max(array_column($points, 'count'));
    $safeMax = max($maxCount, 1);
@endphp

<div class="space-y-2">
    <h2 class="text-base font-semibold text-gs-black-800 mb-1">{{ $title }}</h2>

    @forelse ($points as $point)
        <div class="grid grid-cols-[110px_1fr_40px] gap-3 items-center">
            <p class="text-xs text-gs-black-600">{{ $point['date'] }}</p>
            <div class="h-2 rounded bg-gray-100 overflow-hidden">
                <div
                    class="h-2 rounded bg-gs-primary-500"
                    style="width: {{ (int) round(($point['count'] / $safeMax) * 100) }}%"
                ></div>
            </div>
            <p class="text-xs text-right text-gs-black-700">{{ $point['count'] }}</p>
        </div>
    @empty
        <p class="text-sm text-gs-black-600">No data available.</p>
    @endforelse
</div>
