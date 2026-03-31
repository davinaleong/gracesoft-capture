<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Form' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .theme-default { border-top: 4px solid #111827; }
        .theme-sunrise { border-top: 4px solid #ea580c; background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%); }
        .theme-forest { border-top: 4px solid #166534; background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%); }
    </style>
</head>
<body class="bg-gs-black-50 text-gs-black-900 min-h-screen py-8 px-4">
    <main class="mx-auto w-full max-w-3xl">
        @yield('content')
    </main>
</body>
</html>
