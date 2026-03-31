@props([
    'action' => null,
])

@php
    $activeAccountId = request()->attributes->get('access.account_id')
        ?? session('active_account_id')
        ?? request()->query('account_id');
@endphp

<form method="get" action="{{ $action ?? url()->current() }}" class="flex items-center gap-2 rounded border border-gs-purple-200 bg-gs-purple-50 px-2 py-1">
    <label for="account_id_switcher" class="text-xs font-semibold uppercase tracking-wide text-gs-purple-700">
        Account
    </label>

    <input
        id="account_id_switcher"
        name="account_id"
        value="{{ (string) $activeAccountId }}"
        placeholder="account UUID"
        class="w-56 rounded border border-gs-purple-200 bg-white px-2 py-1 text-xs text-gs-black-800"
    >

    <x-ui.button type="submit" size="sm" variant="primary">Switch</x-ui.button>
</form>
