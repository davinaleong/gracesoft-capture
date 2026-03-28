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
    <main class="container mx-auto p-4 md:p-6 space-y-4">
        <x-ui.card class="p-3 md:p-4">
            <nav class="flex flex-wrap items-center gap-2 md:gap-3">
                <x-ui.button tag="a" href="{{ route('manage.forms.index') }}" variant="secondary" size="sm">Forms</x-ui.button>
                <x-ui.button tag="a" href="{{ route('inbox.index') }}" variant="secondary" size="sm">Inbox</x-ui.button>
                <x-ui.button tag="a" href="{{ route('support.create') }}" variant="secondary" size="sm">Contact Support</x-ui.button>
            </nav>
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
