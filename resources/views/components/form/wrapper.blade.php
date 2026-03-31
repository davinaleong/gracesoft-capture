@props([
    'title',
    'description' => null,
])

<x-ui.card class="space-y-4">
    <h1 class="text-2xl font-bold">{{ $title }}</h1>

    @if (is_string($description) && $description !== '')
        <p class="text-gs-black-700">{{ $description }}</p>
    @endif

    {{ $slot }}
</x-ui.card>
