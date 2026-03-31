@props(['visibility' => 'internal'])

@php
    $value = strtolower((string) $visibility);

    $styles = [
        'internal' => 'bg-gs-black-100 text-gs-black-700 border-gs-black-200',
        'external' => 'bg-blue-50 text-blue-700 border-blue-200',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded border px-2 py-0.5 text-xs font-semibold uppercase tracking-wide', $styles[$value] ?? $styles['internal']]) }}>
    {{ $value === 'external' ? 'External' : 'Internal' }}
</span>
