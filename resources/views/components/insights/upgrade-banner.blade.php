@props([
    'message' => 'Insights are available on Pro plans only.',
])

<x-ui.alert variant="info">
    {{ $message }}
</x-ui.alert>
