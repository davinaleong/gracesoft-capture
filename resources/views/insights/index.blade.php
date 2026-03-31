@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gs-black-800">Insights</h1>
                <p class="text-sm text-gs-black-600">Account: {{ $accountId }}</p>
            </div>

            <form method="get" action="{{ route('insights.index') }}" class="flex items-center gap-2">
                <x-ui.select name="days" id="days">
                    <option value="7" @selected($days === 7)>Last 7 days</option>
                    <option value="14" @selected($days === 14)>Last 14 days</option>
                    <option value="30" @selected($days === 30)>Last 30 days</option>
                </x-ui.select>
                <x-ui.button type="submit">Apply</x-ui.button>
            </form>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-4">
        <x-ui.card>
            <p class="text-sm text-gs-black-600">Total enquiries</p>
            <p class="text-2xl font-semibold text-gs-black-900">{{ $summary['total_enquiries'] }}</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-gs-black-600">Conversion rate</p>
            <p class="text-2xl font-semibold text-gs-black-900">{{ number_format((float) $summary['conversion_rate_percent'], 1) }}%</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-gs-black-600">Avg first response</p>
            <p class="text-2xl font-semibold text-gs-black-900">{{ number_format((float) $summary['avg_first_response_minutes'], 1) }} min</p>
        </x-ui.card>
    </div>

    <x-ui.card>
        <h2 class="text-base font-semibold text-gs-black-800 mb-3">Enquiries per day</h2>

        @php
            $maxCount = max(array_column($summary['daily_enquiries'], 'count'));
            $safeMax = max($maxCount, 1);
        @endphp

        <div class="space-y-2">
            @forelse ($summary['daily_enquiries'] as $point)
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
                <p class="text-sm text-gs-black-600">No enquiry data available.</p>
            @endforelse
        </div>
    </x-ui.card>
@endsection
