@props([
    'value' => '',
    'visible' => false,
    'mask' => '••••••',
])

<span {{ $attributes }}>
    {{ $visible ? $value : $mask }}
</span>
