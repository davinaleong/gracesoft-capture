@props([
    'title',
    'description' => null,
    'surface' => 'card',
])

@if ($surface === 'none')
    <div {{ $attributes->class(['w-full max-w-none space-y-4 bg-transparent']) }}>
        <h1 class="text-2xl font-bold">{{ $title }}</h1>

        @if (is_string($description) && $description !== '')
            <p class="text-gs-black-700">{{ $description }}</p>
        @endif

        {{ $slot }}
    </div>
@else
    <x-ui.card {{ $attributes->class(['space-y-4']) }}>
        <h1 class="text-2xl font-bold">{{ $title }}</h1>

        @if (is_string($description) && $description !== '')
            <p class="text-gs-black-700">{{ $description }}</p>
        @endif

        {{ $slot }}
    </x-ui.card>
@endif
