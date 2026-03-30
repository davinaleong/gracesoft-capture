@extends('layouts.app')

@section('content')
    <x-ui.card class="max-w-lg">
        <h1 class="mb-2 text-xl font-semibold">Administrator Sign In</h1>
        <p class="mb-4 text-sm text-gs-black-700">Use your administrator identity for compliance and platform oversight actions.</p>

        <form method="post" action="{{ route('admin.login.store') }}" class="space-y-4">
            @csrf

            <x-ui.field for="email" label="Admin Email">
                <x-ui.input id="email" name="email" type="email" :value="old('email')" required autocomplete="email" />
            </x-ui.field>

            <x-ui.field for="password" label="Password">
                <x-ui.input id="password" name="password" type="password" required autocomplete="current-password" />
            </x-ui.field>

            <label class="inline-flex items-center gap-2 text-sm text-gs-black-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-gs-black-300" />
                Remember me
            </label>

            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="danger">Admin Sign In</x-ui.button>
                <x-ui.button tag="a" href="{{ route('admin.password.request') }}" variant="secondary">Forgot Password</x-ui.button>
                <x-ui.button tag="a" href="{{ route('login') }}" variant="secondary">User Login</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
