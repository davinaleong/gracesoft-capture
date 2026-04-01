@extends('layouts.auth')

@section('content')
    <x-ui.card class="max-w-2xl space-y-4">
        <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-700">
            Checkout Cancelled
        </span>

        <h1 class="text-2xl font-semibold text-gs-black-900">No changes were made</h1>
        <p class="text-sm text-gs-black-700">
            Your payment session was cancelled before completion. You can safely retry any time.
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button tag="a" href="{{ route('register') }}" variant="secondary">View Plans</x-ui.button>
            <x-ui.button tag="a" href="{{ route('support.create') }}" variant="neutral">Need help?</x-ui.button>
        </div>

        @if (auth('web')->check() && is_string($plan ?? null))
            <div class="rounded border border-gs-purple-200 bg-gs-purple-50 p-3">
                <p class="text-sm text-gs-purple-800">Retry checkout for {{ strtoupper($plan) }} plan.</p>
                <form method="post" action="{{ route('billing.checkout') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $plan }}">
                    <x-ui.button type="submit">Retry checkout</x-ui.button>
                </form>
            </div>
        @endif
    </x-ui.card>
@endsection
