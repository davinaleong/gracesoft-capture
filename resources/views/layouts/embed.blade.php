<!doctype html>
<html lang="en">
<head>
    @php
        $seoTitle = $title ?? 'Form';
        $seoDescription = $metaDescription ?? 'Secure form collection powered by GraceSoft Capture.';
        $seoCanonical = $canonicalUrl ?? url()->current();
        $seoImage = $metaImage ?? asset('og.png');
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

    <title>{{ $seoTitle }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .theme-default { border-top: 0; }
        .theme-sunrise { border-top: 0; background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%); }
        .theme-forest { border-top: 0; background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%); }
    </style>
</head>
@php
    $resolvedEmbedSurface = $embedSurface ?? 'card';
    $isTransparentSurface = $resolvedEmbedSurface === 'none';
@endphp
<body class="{{ $isTransparentSurface ? 'bg-transparent text-gs-black-900 min-h-0 p-0' : 'bg-gs-black-50 text-gs-black-900 min-h-screen py-8 px-4' }}">
    <main class="{{ $isTransparentSurface ? 'w-full' : 'mx-auto w-full max-w-3xl' }}">
        @yield('content')
    </main>
</body>
</html>
