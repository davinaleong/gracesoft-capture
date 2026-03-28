@props([
    'variant' => 'primary',
    'size' => 'md',
    'tag' => 'button',
    'type' => 'button',
    'href' => null,
])

@php
    $base = 'inline-flex items-center gap-2 rounded border font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';

    $variantClasses = [
        'primary' => 'text-white bg-gs-purple-600 border-gs-purple-600 hover:bg-gs-purple-700 hover:border-gs-purple-700 focus-visible:ring-gs-purple-300',
        'secondary' => 'text-gs-purple-600 bg-white border-gs-purple-600 hover:bg-gs-purple-50 focus-visible:ring-gs-purple-200',
        'danger' => 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100 focus-visible:ring-red-200',
        'success' => 'text-green-700 bg-green-50 border-green-200 hover:bg-green-100 focus-visible:ring-green-200',
        'neutral' => 'text-gs-black-700 bg-gs-black-50 border-gs-black-200 hover:bg-gs-black-100 focus-visible:ring-gs-black-200',
    ];

    $sizeClasses = [
        'sm' => 'px-2 py-1 text-sm',
        'md' => 'px-3 py-2 text-sm',
        'lg' => 'px-4 py-2.5 text-base',
    ];

    $classes = trim($base . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']));
@endphp

@if ($tag === 'a')
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </button>
@endif
