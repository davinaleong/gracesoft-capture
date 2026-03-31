@props([
    'message' => 'You do not have permission to perform this action.',
])

<x-ui.alert variant="error" {{ $attributes }}>
    {{ $message }}
</x-ui.alert>
