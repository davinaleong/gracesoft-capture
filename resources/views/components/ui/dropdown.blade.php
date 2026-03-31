@props([
    'label' => 'Options',
])

<details {{ $attributes->class(['relative']) }}>
    <summary class="inline-flex cursor-pointer list-none items-center rounded border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gs-black-700">
        {{ $label }}
    </summary>

    <div class="absolute z-20 mt-2 min-w-40 rounded border border-gray-200 bg-white p-2 shadow">
        {{ $slot }}
    </div>
</details>
