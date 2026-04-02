@extends('layouts.app')

@section('content')
    @php
        $selectedPlan = is_string($selectedPlan ?? null) ? $selectedPlan : 'growth';
    @endphp

    <x-ui.card class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gs-black-900">Choose your plan before checkout</h1>
                <p class="mt-1 text-sm text-gs-black-700">Review what each plan includes, then continue to Stripe.</p>
            </div>
            <x-ui.button tag="a" href="{{ route('manage.forms.index') }}" variant="secondary" size="sm">Back to dashboard</x-ui.button>
        </div>

        <div class="grid gap-3 lg:grid-cols-3">
            @foreach (($plans ?? []) as $slug => $plan)
                @php
                    $isSelected = $selectedPlan === $slug;
                    $isPaid = in_array($slug, ['growth', 'pro'], true);
                @endphp

                <article class="rounded border {{ $isSelected ? 'border-gs-purple-300 bg-gs-purple-50' : 'border-gs-black-200 bg-white' }} p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-base font-semibold text-gs-black-900">{{ $plan['name'] }}</p>
                            <p class="text-sm font-semibold text-gs-purple-700">{{ $plan['price'] }}</p>
                        </div>
                        @if ($isSelected)
                            <x-ui.badge variant="primary">Selected</x-ui.badge>
                        @endif
                    </div>

                    <p class="mt-2 text-sm font-medium text-gs-black-800">{{ $plan['headline'] }}</p>

                    <ul class="mt-3 space-y-1 text-sm text-gs-black-700">
                        @foreach ($plan['features'] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>

                    <p class="mt-3 rounded border border-gs-purple-200 bg-gs-purple-50 px-2 py-1 text-xs font-medium text-gs-purple-800">
                        Best for: {{ $plan['best_for'] }}
                    </p>

                    @if (! $isPaid)
                        <x-ui.button type="button" class="mt-3 w-full justify-center" variant="secondary" disabled>
                            Included by default
                        </x-ui.button>
                    @else
                        <form method="post" action="{{ route('billing.checkout') }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $slug }}">
                            <input type="hidden" name="account_id" value="{{ $billingAccountId }}">
                            <x-ui.button type="submit" class="w-full justify-center" :variant="$isSelected ? 'primary' : 'secondary'">
                                Continue to Stripe for {{ $plan['name'] }} ({{ $plan['price'] }})
                            </x-ui.button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    </x-ui.card>
@endsection
