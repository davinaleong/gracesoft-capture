@props([
    'id' => null,
    'title' => null,
    'maxWidth' => 'max-w-xl',
])

@php
    $containerClass = trim('h-fit w-[calc(100%-1rem)] overflow-y-auto rounded border border-gray-200 p-0 backdrop:bg-black/40 ' . $maxWidth);
@endphp

<dialog
    @if ($id) id="{{ $id }}" @endif
    style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); max-height: calc(100vh - 2rem);"
    {{ $attributes->class([$containerClass]) }}
>
    <x-ui.card class="space-y-4 border-0 shadow-none">
        @if ($title)
            <h2 class="text-xl font-bold">{{ $title }}</h2>
        @endif

        {{ $slot }}
    </x-ui.card>
</dialog>
