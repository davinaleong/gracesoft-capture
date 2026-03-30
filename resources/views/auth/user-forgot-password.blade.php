@extends('layouts.app')

@section('content')
    <x-ui.card class="max-w-lg">
        <h1 class="mb-2 text-xl font-semibold">Forgot User Password</h1>
        <p class="mb-4 text-sm text-gs-black-700">Enter your account email and we will send a password reset link.</p>

        <form method="post" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <x-ui.field for="email" label="Email">
                <x-ui.input id="email" name="email" type="email" :value="old('email')" required autocomplete="email" />
            </x-ui.field>
            <div class="flex items-center gap-2">
                <x-ui.button type="submit">Send Reset Link</x-ui.button>
                <x-ui.button tag="a" href="{{ route('login') }}" variant="secondary">Back to Login</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
