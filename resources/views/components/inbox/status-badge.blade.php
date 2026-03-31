@props([
    'status',
])

@php
    $statusVariant = match ($status) {
        'new' => 'info',
        'contacted' => 'primary',
        'closed' => 'success',
        default => 'neutral',
    };
@endphp

<x-ui.badge :variant="$statusVariant">{{ ucfirst((string) $status) }}</x-ui.badge>
