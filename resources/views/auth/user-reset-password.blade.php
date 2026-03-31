@extends('layouts.auth')

@section('content')
    <x-ui.card class="max-w-xl">
        <h1 class="mb-2 text-xl font-semibold">Reset User Password</h1>

        <form method="post" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}" />

            <x-ui.field for="email" label="Email">
                <x-ui.input id="email" name="email" type="email" :value="old('email', $email)" required autocomplete="email" />
            </x-ui.field>

            <x-ui.field for="password" label="New Password">
                <x-ui.input id="password" name="password" type="password" required autocomplete="new-password" />
            </x-ui.field>

            <x-ui.field for="password_confirmation" label="Confirm Password">
                <x-ui.input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
            </x-ui.field>

            <x-ui.button type="submit">Reset Password</x-ui.button>
        </form>
    </x-ui.card>
@endsection
