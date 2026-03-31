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
        $showAdminLoginLinks = (bool) config('capture.features.show_admin_login_links', false);
    @endphp

    <main class="container mx-auto min-h-screen px-4 py-6 md:px-6">
        <div class="mx-auto max-w-3xl space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <img src="{{ asset('wm.svg') }}" alt="GraceSoft" style="width: 150px; height: auto;">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.button tag="a" href="{{ route('login') }}" size="sm" variant="secondary">User Login</x-ui.button>
                    <x-ui.button tag="a" href="{{ route('register') }}" size="sm" variant="secondary">Register</x-ui.button>
                    @if ($showAdminLoginLinks)
                        <x-ui.button tag="a" href="{{ route('admin.login') }}" size="sm" variant="danger">Admin Login</x-ui.button>
                    @endif
                </div>
            </div>

            @if (session('status'))
                <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
            @endif

            @if ($errors->any())
                <x-ui.alert variant="error">{{ $errors->first() }}</x-ui.alert>
            @endif

            @yield('content')
        </div>
    </main>
</body>
</html>
