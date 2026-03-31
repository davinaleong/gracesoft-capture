@props([
    'code',
    'token',
])

<div class="space-y-2">
    <x-ui.textarea rows="3" readonly>{{ $code }}</x-ui.textarea>
    <x-integration.copy-button :code="$code" :token="$token" />
</div>
