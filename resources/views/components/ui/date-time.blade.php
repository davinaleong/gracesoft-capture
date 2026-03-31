@props([
    'value' => null,
    'format' => 'Y-m-d H:i',
])

@php
    $date = $value instanceof \Carbon\CarbonInterface ? $value : ($value ? \Illuminate\Support\Carbon::parse($value) : null);
@endphp

@if ($date)
    <time datetime="{{ $date->toIso8601String() }}" {{ $attributes }}>
        {{ $date->format($format) }}
    </time>
@endif
