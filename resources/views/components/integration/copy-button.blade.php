@props([
    'code',
    'token',
])

@php
    $buttonId = 'copy-embed-' . $token;
@endphp

<x-ui.button
    type="button"
    variant="neutral"
    size="sm"
    id="{{ $buttonId }}"
    onclick="navigator.clipboard.writeText(@js($code)); this.innerText='Copied'; setTimeout(() => this.innerText='Copy embed code', 1200);"
>
    Copy embed code
</x-ui.button>
