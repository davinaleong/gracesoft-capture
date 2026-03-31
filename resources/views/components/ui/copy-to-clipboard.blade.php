@props([
    'value' => '',
    'label' => 'Copy',
])

<x-ui.button
    type="button"
    variant="secondary"
    size="sm"
    onclick="navigator.clipboard?.writeText(@js($value))"
    {{ $attributes }}
>
    {{ $label }}
</x-ui.button>
