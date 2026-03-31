@props([
    'role' => 'viewer',
])

@php
    $value = strtolower((string) $role);

    $variant = match ($value) {
        'owner' => 'danger',
        'member' => 'primary',
        default => 'neutral',
    };
@endphp

<x-ui.badge :variant="$variant" {{ $attributes }}>{{ ucfirst($value) }}</x-ui.badge>
