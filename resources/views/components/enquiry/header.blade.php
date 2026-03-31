@props([
    'enquiry',
])

<div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gs-black-900">{{ $enquiry->subject }}</h1>
        <p class="text-sm text-gs-black-600">Form: {{ $enquiry->form?->name ?? 'Unknown form' }}</p>
    </div>

    <div class="grid grid-cols-1 gap-2 text-sm md:text-right">
        <p><span class="font-semibold text-gs-black-700">Name:</span> {{ $enquiry->name }}</p>
        <p><span class="font-semibold text-gs-black-700">Email:</span> {{ $enquiry->email }}</p>
        <div>
            <x-inbox.status-badge :status="$enquiry->status" />
        </div>
    </div>
</div>
