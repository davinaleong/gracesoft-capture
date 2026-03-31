@props([
    'consented' => false,
])

<x-ui.badge :variant="$consented ? 'success' : 'neutral'" {{ $attributes }}>
    {{ $consented ? 'Consent Recorded' : 'Consent Missing' }}
</x-ui.badge>
