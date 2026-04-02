@extends('layouts.auth')

@section('content')
    <x-ui.card class="max-w-lg">
        <h1 class="mb-2 text-xl font-semibold">User Sign In</h1>
        <p class="mb-4 text-sm text-gs-black-700">Sign in to access your forms, inbox, and collaborators.</p>

        @if (is_string($upgradePlan ?? null) && $upgradePlan !== '')
            <div class="mb-4 rounded border border-gs-purple-200 bg-gs-purple-50 px-3 py-2 text-sm text-gs-purple-800">
                Sign in to continue with {{ ucfirst($upgradePlan) }} plan in your dashboard.
            </div>
        @endif

        <form method="post" action="{{ route('login.store') }}" class="space-y-4">
            @csrf

            <x-ui.field for="email" label="Email">
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
                <x-ui.button type="submit">Sign In</x-ui.button>
                <x-ui.button tag="a" href="{{ route('register') }}" variant="secondary">Create Account</x-ui.button>
                <x-ui.button tag="a" href="{{ route('password.request') }}" variant="secondary">Forgot Password</x-ui.button>
                @if (config('capture.features.show_admin_login_links', false))
                    <x-ui.button tag="a" href="{{ route('admin.login') }}" variant="secondary">Admin Login</x-ui.button>
                @endif
            </div>
        </form>
    </x-ui.card>
@endsection
