@props([
    'variant' => 'neutral',
])

@php
    $variantClasses = [
        'neutral' => 'text-gray-600 bg-gray-50',
        'info' => 'text-blue-600 bg-blue-50',
        'primary' => 'text-gs-purple-600 bg-gs-purple-50',
        'success' => 'text-green-600 bg-green-50',
        'danger' => 'text-red-600 bg-red-50',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-1 text-sm font-medium', $variantClasses[$variant] ?? $variantClasses['neutral']]) }}>
    {{ $slot }}
</span>
