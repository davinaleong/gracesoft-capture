<!doctype html>
<html lang="en">
<head>
    @php
        $seoTitle = $title ?? 'GraceSoft Capture';
        $seoDescription = $metaDescription ?? 'Access your GraceSoft Capture account securely.';
        $seoCanonical = $canonicalUrl ?? url()->current();
        $seoImage = $metaImage ?? asset('logo.svg');
        $seoRobots = $metaRobots ?? 'noindex,nofollow';
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    <title>{{ $seoTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    @stack('meta')
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
                    <x-ui.button tag="a" href="{{ url('/') }}" size="sm" variant="neutral">Back to Landing</x-ui.button>
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

            <div class="flex flex-wrap items-center gap-3 border-t border-gs-black-100 pt-3 text-xs text-gs-black-600">
                <a href="{{ route('legal.privacy') }}" class="underline decoration-gs-black-300 underline-offset-2 hover:text-gs-black-800">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}" class="underline decoration-gs-black-300 underline-offset-2 hover:text-gs-black-800">Terms and Conditions</a>
            </div>
        </div>
    </main>
</body>
</html>
