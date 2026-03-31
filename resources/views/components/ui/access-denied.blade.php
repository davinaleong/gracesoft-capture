@props([
    'message' => 'You do not have permission to access this resource.',
])

<x-security.access-denied :message="$message" {{ $attributes }} />
