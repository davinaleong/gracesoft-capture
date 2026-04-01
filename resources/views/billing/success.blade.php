@extends('layouts.auth')

@section('content')
    <x-ui.card class="max-w-2xl space-y-4">
        <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-green-700">
            Billing Updated
        </span>

        <h1 class="text-2xl font-semibold text-gs-black-900">Payment successful</h1>
        <p class="text-sm text-gs-black-700">
            Your checkout was completed successfully. Your selected plan is now active.
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button tag="a" href="{{ route('manage.forms.index') }}">Go to Forms</x-ui.button>
            <x-ui.button tag="a" href="{{ route('integrations.index') }}" variant="secondary">Open Integrations</x-ui.button>
        </div>
    </x-ui.card>
@endsection
