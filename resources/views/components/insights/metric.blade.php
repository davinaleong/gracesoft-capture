@props([
    'label',
    'value',
    'suffix' => '',
])

<div>
    <p class="text-sm text-gs-black-600">{{ $label }}</p>
    <p class="text-2xl font-semibold text-gs-black-900">{{ $value }}{{ $suffix }}</p>
</div>
