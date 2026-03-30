@extends('layouts.app')

@section('content')
    <x-ui.card class="max-w-lg">
        <h1 class="mb-2 text-xl font-semibold">Forgot Admin Password</h1>
        <p class="mb-4 text-sm text-gs-black-700">Enter your administrator email and we will send a password reset link.</p>

        <form method="post" action="{{ route('admin.password.email') }}" class="space-y-4">
            @csrf
            <x-ui.field for="email" label="Admin Email">
                <x-ui.input id="email" name="email" type="email" :value="old('email')" required autocomplete="email" />
            </x-ui.field>
            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="danger">Send Reset Link</x-ui.button>
                <x-ui.button tag="a" href="{{ route('admin.login') }}" variant="secondary">Back to Admin Login</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
