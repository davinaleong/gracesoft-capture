@props([
    'isOwner' => false,
])

@if ($isOwner)
    <x-ui.alert variant="info">
        You are an owner for this account. You can invite, resend, revoke, and remove collaborators.
    </x-ui.alert>
@else
    <x-ui.alert variant="error">
        Owner-only controls are disabled for your role. Ask an owner to manage collaborators.
    </x-ui.alert>
@endif
