@props([
    'status' => 'new',
])

@php
    $value = strtolower((string) $status);

    $styles = [
        'new' => 'bg-blue-50 text-blue-700',
        'contacted' => 'bg-amber-50 text-amber-700',
        'closed' => 'bg-green-50 text-green-700',
        'active' => 'bg-green-50 text-green-700',
        'inactive' => 'bg-gray-100 text-gray-700',
        'pending' => 'bg-yellow-50 text-yellow-700',
        'failed' => 'bg-red-50 text-red-700',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold uppercase tracking-wide', $styles[$value] ?? 'bg-gray-100 text-gray-700']) }}>
    {{ $value }}
</span>
