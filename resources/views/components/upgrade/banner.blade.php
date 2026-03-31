@props([
    'title' => 'Upgrade Required',
    'message' => 'This feature is not available on your current plan.',
])

<x-ui.alert variant="info" {{ $attributes }}>
    <div class="space-y-1">
        <p class="font-semibold">{{ $title }}</p>
        <p>{{ $message }}</p>
    </div>
</x-ui.alert>
