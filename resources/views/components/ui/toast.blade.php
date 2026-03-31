@props([
    'variant' => 'success',
])

@php
    $variantClasses = [
        'success' => 'bg-green-50 border-green-300 text-green-700',
        'info' => 'bg-blue-50 border-blue-300 text-blue-700',
        'error' => 'bg-red-50 border-red-300 text-red-700',
    ];
@endphp

<div role="status" {{ $attributes->class(['rounded border px-3 py-2 text-sm font-medium', $variantClasses[$variant] ?? $variantClasses['success']]) }}>
    {{ $slot }}
</div>
