@props([
    'type' => 'submit',
])

<x-ui.button :type="$type" {{ $attributes }}>
    {{ $slot }}
</x-ui.button>
