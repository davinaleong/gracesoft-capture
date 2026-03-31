@props([
    'message' => 'Insights are available on Pro plans only.',
])

<x-upgrade.banner message="{{ $message }}" />
