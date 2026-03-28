@props([
    'size' => 20,
    'strokeWidth' => 2,
])

<x-icons.trash-2
    :size="$size"
    :stroke-width="$strokeWidth"
    {{ $attributes }}
/>
