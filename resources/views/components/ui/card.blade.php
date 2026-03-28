@props(['padding' => 'md'])

@php
    $paddingClasses = [
        'none' => '',
        'sm' => 'p-3',
        'md' => 'p-4',
        'lg' => 'p-6',
    ];
@endphp

<div {{ $attributes->class(['bg-white border border-gray-300 rounded shadow', $paddingClasses[$padding] ?? $paddingClasses['md']]) }}>
    {{ $slot }}
</div>
