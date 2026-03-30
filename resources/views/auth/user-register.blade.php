@extends('layouts.app')

@section('content')
    <x-ui.card class="max-w-xl">
        <h1 class="mb-2 text-xl font-semibold">Create User Account</h1>
        <p class="mb-4 text-sm text-gs-black-700">Register your user identity to access form management and inbox tools.</p>

        <form method="post" action="{{ route('register.store') }}" class="space-y-4">
            @csrf

            <x-ui.field for="name" label="Name">
                <x-ui.input id="name" name="name" :value="old('name')" required autocomplete="name" />
            </x-ui.field>

            <x-ui.field for="email" label="Email">
                <x-ui.input id="email" name="email" type="email" :value="old('email')" required autocomplete="email" />
            </x-ui.field>

            <x-ui.field for="password" label="Password">
                <x-ui.input id="password" name="password" type="password" required autocomplete="new-password" />
            </x-ui.field>

            <x-ui.field for="password_confirmation" label="Confirm Password">
                <x-ui.input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
            </x-ui.field>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit">Create Account</x-ui.button>
                <x-ui.button tag="a" href="{{ route('login') }}" variant="secondary">Back to Login</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
