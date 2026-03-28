@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $variantClasses = [
        'success' => 'bg-green-50 border-green-300 text-green-700',
        'info' => 'bg-gs-purple-50 border-gs-purple-300 text-gs-purple-700',
        'error' => 'bg-red-50 border-red-300 text-red-700',
    ];
@endphp

<div {{ $attributes->class(['border rounded p-4 space-y-1', $variantClasses[$variant] ?? $variantClasses['info']]) }}>
    @if ($title)
        <h2 class="text-xl font-bold">{{ $title }}</h2>
    @endif

    {{ $slot }}
</div>
