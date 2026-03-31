@props([
    'message' => 'You do not have permission to perform this action.',
])

<x-ui.alert variant="error">
    {{ $message }}
</x-ui.alert>
