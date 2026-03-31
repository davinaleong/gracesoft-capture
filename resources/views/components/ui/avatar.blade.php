@props([
    'name' => null,
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => 'h-6 w-6 text-xs',
        'md' => 'h-8 w-8 text-sm',
        'lg' => 'h-10 w-10 text-base',
    ];

    $initials = collect(explode(' ', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class(['inline-flex items-center justify-center rounded-full bg-gs-purple-100 font-semibold text-gs-purple-700', $sizeClasses[$size] ?? $sizeClasses['md']]) }}>
    {{ $initials !== '' ? $initials : '?' }}
</span>
