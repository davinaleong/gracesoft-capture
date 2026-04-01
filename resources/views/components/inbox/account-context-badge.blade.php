@props([
    'accountId' => null,
])

@if (is_string($accountId) && $accountId !== '')
    <x-ui.badge variant="neutral">Workspace context active</x-ui.badge>
@endif
