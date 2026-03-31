@props([
    'selectedStatus' => '',
    'search' => '',
])

<form method="get" action="{{ route('inbox.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,180px)_minmax(0,1fr)_auto] md:items-end">
    <x-ui.field for="status" label="Filter Status">
        <x-ui.select id="status" name="status">
            <option value="" @selected($selectedStatus === '')>All</option>
            <option value="new" @selected($selectedStatus === 'new')>New</option>
            <option value="contacted" @selected($selectedStatus === 'contacted')>Contacted</option>
            <option value="closed" @selected($selectedStatus === 'closed')>Closed</option>
        </x-ui.select>
    </x-ui.field>

    <x-ui.field for="search" label="Search">
        <x-ui.input id="search" name="search" :value="$search" placeholder="Name, email, or subject" />
    </x-ui.field>

    <div class="flex items-center gap-2">
        <x-ui.button type="submit">Apply</x-ui.button>
    </div>
</form>
