@props([
    'size' => 20,
    'strokeWidth' => 2,
    'viewBox' => '0 0 24 24',
])

<svg
    xmlns="http://www.w3.org/2000/svg"
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="{{ $viewBox }}"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    {{ $attributes }}
>
    {{ $slot }}
</svg>
