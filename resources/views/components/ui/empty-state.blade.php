@props([
    'title' => 'No data yet',
    'message' => null,
])

<div {{ $attributes->class(['rounded border border-dashed border-gray-300 bg-gray-50 p-6 text-center']) }}>
    <p class="text-sm font-semibold text-gs-black-800">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 text-sm text-gs-black-600">{{ $message }}</p>
    @endif

    {{ $slot }}
</div>
