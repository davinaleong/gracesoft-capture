@props([
    'enquiry',
])

<div class="space-y-2">
    <h2 class="text-lg font-semibold text-gs-black-900">Message</h2>
    <p class="rounded border border-gray-200 bg-gray-50 p-3 text-gs-black-800">{{ $enquiry->message }}</p>
</div>
