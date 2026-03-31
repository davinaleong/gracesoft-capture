@props([
    'label' => 'Loading...',
])

<div {{ $attributes->class(['inline-flex items-center gap-2 text-sm text-gs-black-600']) }}>
    <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-gs-purple-200 border-t-gs-purple-600"></span>
    <span>{{ $label }}</span>
</div>
