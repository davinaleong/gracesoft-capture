@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gs-black-800">Insights</h1>
                <p class="text-sm text-gs-black-600">Workspace insights overview</p>
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
        <x-insights.card>
            <x-insights.metric label="Total enquiries" :value="$summary['total_enquiries']" />
        </x-insights.card>

        <x-insights.card>
            <x-insights.metric label="Conversion rate" :value="number_format((float) $summary['conversion_rate_percent'], 1)" suffix="%" />
        </x-insights.card>

        <x-insights.card>
            <x-insights.metric label="Avg first response" :value="number_format((float) $summary['avg_first_response_minutes'], 1)" suffix=" min" />
        </x-insights.card>
    </div>

    @if ((int) ($summary['total_enquiries'] ?? 0) === 0)
        <x-ui.card class="mb-4 border border-gs-purple-200 bg-gs-purple-50">
            <h2 class="text-base font-semibold text-gs-black-900">No insights data yet</h2>
            <p class="mt-1 text-sm text-gs-black-700">Insights populate after enquiries are received and processed. Create a form, publish it, and submit a few test enquiries to generate trend data.</p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-ui.button tag="a" href="{{ route('manage.forms.create') }}" size="sm" class="px-4">Create Form</x-ui.button>
                <x-ui.button tag="a" href="{{ route('inbox.index') }}" size="sm" variant="secondary">Go to Inbox</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-insights.card>
            <x-insights.chart-line title="Enquiries per day" :points="$summary['daily_enquiries']" />
        </x-insights.card>

        <x-insights.card>
            <x-insights.chart-funnel title="Conversion funnel" :stages="$summary['funnel']" />
        </x-insights.card>
    </div>
@endsection
