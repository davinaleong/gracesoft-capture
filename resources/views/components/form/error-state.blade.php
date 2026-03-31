@props([
    'message' => 'Please check your input and try again.',
])

<x-ui.alert variant="error">{{ $message }}</x-ui.alert>
