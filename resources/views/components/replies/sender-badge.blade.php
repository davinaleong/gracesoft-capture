@props([
    'senderType',
])

@php
    $variant = match ($senderType) {
        'administrator' => 'danger',
        'external' => 'primary',
        'system' => 'neutral',
        default => 'info',
    };
@endphp

<x-ui.badge :variant="$variant">{{ ucfirst((string) $senderType) }}</x-ui.badge>
