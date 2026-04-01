@props([
    'id' => null,
    'title' => null,
    'maxWidth' => 'max-w-xl',
])

@php
    $containerClass = trim('fixed left-1/2 top-1/2 h-fit max-h-[calc(100vh-2rem)] w-[calc(100%-1rem)] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded border border-gray-200 p-0 backdrop:bg-black/40 ' . $maxWidth);
@endphp

<dialog @if ($id) id="{{ $id }}" @endif {{ $attributes->class([$containerClass]) }}>
    <x-ui.card class="space-y-4 border-0 shadow-none">
        @if ($title)
            <h2 class="text-xl font-bold">{{ $title }}</h2>
        @endif

        {{ $slot }}
    </x-ui.card>
</dialog>
