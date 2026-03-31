@props([
    'target' => 'feedback-modal',
    'label' => 'Contact Support',
    'variant' => 'secondary',
])

<x-ui.button
    type="button"
    :variant="$variant"
    onclick="document.getElementById('{{ $target }}')?.showModal()"
    {{ $attributes }}
>
    {{ $label }}
</x-ui.button>
