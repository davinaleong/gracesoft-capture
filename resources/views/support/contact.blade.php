@extends('layouts.app')

@section('content')
    <x-ui.card class="space-y-4">
        <h1 class="text-2xl font-bold">Contact Support</h1>
        <p class="text-gs-black-700">Send your issue or question and we will forward it to HQ support.</p>

        <form method="post" action="{{ route('support.store') }}">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.field for="name" label="Name" required>
                    <x-ui.input id="name" name="name" :value="old('name')" required />
                </x-ui.field>

                <x-ui.field for="email" label="Email" required>
                    <x-ui.input id="email" name="email" type="email" :value="old('email')" required />
                </x-ui.field>

                <x-ui.field for="subject" label="Subject" required class="md:col-span-2">
                    <x-ui.input id="subject" name="subject" :value="old('subject')" required />
                </x-ui.field>

                <x-ui.field for="message" label="Message" required class="md:col-span-2">
                    <x-ui.textarea id="message" name="message" rows="5" required>{{ old('message') }}</x-ui.textarea>
                </x-ui.field>

                <x-ui.field for="account_id" label="Account ID (optional)" class="md:col-span-2">
                    <x-ui.input id="account_id" name="account_id" :value="old('account_id')" />
                </x-ui.field>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <x-ui.button type="submit">Send to Support</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
