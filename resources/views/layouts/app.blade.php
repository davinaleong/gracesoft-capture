<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    <title>{{ $title ?? 'GraceSoft Capture' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gs-black-50 text-gs-black-900">
    @php
        $user = auth('web')->user();
        $admin = auth('admin')->user();
        $showAdminLoginLinks = (bool) config('capture.features.show_admin_login_links', false);
    @endphp

    <main class="container mx-auto p-4 md:p-6 space-y-4">
        <x-ui.card class="p-3 md:p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <nav class="hidden items-center gap-2 md:flex md:gap-3">
                    <x-ui.button tag="a" href="{{ route('manage.forms.index') }}" variant="secondary" size="sm">Forms</x-ui.button>
                    <x-ui.button tag="a" href="{{ route('inbox.index') }}" variant="secondary" size="sm">Inbox</x-ui.button>
                    <x-ui.button tag="a" href="{{ route('integrations.index') }}" variant="secondary" size="sm">Integrations</x-ui.button>
                    <x-ui.button tag="a" href="{{ route('support.create') }}" variant="secondary" size="sm">Contact Support</x-ui.button>
                </nav>

                <div class="md:hidden">
                    <x-ui.dropdown label="Menu" class="w-full">
                        <div class="flex min-w-40 flex-col gap-1">
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('manage.forms.index') }}">Forms</a>
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('inbox.index') }}">Inbox</a>
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('integrations.index') }}">Integrations</a>
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('support.create') }}">Contact Support</a>
                        </div>
                    </x-ui.dropdown>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($admin)
                        <span class="inline-flex h-9 items-center rounded border border-red-200 bg-red-50 px-2 text-xs font-semibold uppercase tracking-wide text-red-800">
                            Admin Session: {{ $admin->display_name }}
                        </span>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="danger">Admin Logout</x-ui.button>
                        </form>
                    @elseif ($user)
                        <span class="inline-flex h-9 items-center rounded border border-gs-purple-200 bg-gs-purple-50 px-2 text-xs font-semibold uppercase tracking-wide text-gs-purple-700">
                            User Session: {{ $user->email }}
                        </span>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="neutral">Logout</x-ui.button>
                        </form>
                    @else
                        <x-ui.button tag="a" href="{{ route('login') }}" size="sm">User Login</x-ui.button>
                        @if ($showAdminLoginLinks)
                            <x-ui.button tag="a" href="{{ route('admin.login') }}" size="sm" variant="danger">Admin Login</x-ui.button>
                        @endif
                    @endif
                </div>
            </div>
        </x-ui.card>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="error">{{ $errors->first() }}</x-ui.alert>
        @endif

        @yield('content')
    </main>
</body>
</html>
