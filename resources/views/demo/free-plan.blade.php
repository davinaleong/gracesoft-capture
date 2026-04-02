@extends('layouts.auth', ['title' => 'Free Plan Demo'])

@section('content')
    <x-form.wrapper title="Capture Demo (Free Plan)" description="Try a real submission flow exactly like a Free plan enquiry form.">
        <div class="rounded border border-gs-purple-200 bg-gs-purple-50 p-3 text-sm text-gs-purple-800">
            <p class="font-semibold">Demo data policy</p>
            <p class="mt-1">Submissions here are temporary test data, stored short-term only, and not saved in permanent enquiry records.</p>
        </div>

        @if (session('status'))
            <x-form.success-state :message="session('status')" />
        @endif

        @if ($errors->any())
            <x-form.error-state />
        @endif

        <form action="{{ route('demo.free.submit') }}" method="post" novalidate>
            @csrf

            <div class="sr-only" aria-hidden="true">
                <label for="website">Leave this field empty</label>
                <x-ui.input id="website" name="website" tabindex="-1" autocomplete="off" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-form.input
                    id="name"
                    name="name"
                    label="Full Name"
                    type="text"
                    autocomplete="name"
                    :value="old('name')"
                    :required="true"
                />

                <x-form.input
                    id="email"
                    name="email"
                    label="Email Address"
                    type="email"
                    autocomplete="email"
                    :value="old('email')"
                    :required="true"
                />

                <x-form.input
                    id="subject"
                    name="subject"
                    label="Subject"
                    type="text"
                    :value="old('subject')"
                    :required="true"
                    class="md:col-span-2"
                />

                <x-form.textarea
                    id="message"
                    name="message"
                    label="Message"
                    :value="old('message')"
                    :rows="5"
                    :required="true"
                    class="md:col-span-2"
                />

                <div class="md:col-span-2 flex flex-wrap items-center gap-2">
                    <x-form.button type="submit">Submit Demo Enquiry</x-form.button>
                    <x-ui.button tag="a" href="{{ url('/') }}" variant="secondary">Back to Landing</x-ui.button>
                </div>
            </div>
        </form>
    </x-form.wrapper>
@endsection
