@props([
    'accountId' => null,
])

@if (is_string($accountId) && $accountId !== '')
    <x-ui.badge variant="neutral">Account: {{ $accountId }}</x-ui.badge>
@endif
