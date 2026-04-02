@props([
    'enquiry',
])

@php
    $firstReplyAt = optional($enquiry->replies)->sortBy('created_at')->first()?->created_at;
    $firstResponseAt = $enquiry->contacted_at ?? $firstReplyAt;

    $latestReplyAt = optional($enquiry->replies)->sortByDesc('created_at')->first()?->created_at;
    $latestNoteAt = optional($enquiry->notes)->sortByDesc('created_at')->first()?->created_at;
@endphp

<div class="space-y-3">
    <h2 class="text-lg font-semibold text-gs-black-900">Timeline</h2>

    <ol class="space-y-2">
        <li class="rounded border border-gray-200 bg-white p-3">
            <p class="text-sm font-semibold text-gs-black-800">Enquiry received</p>
            <p class="text-sm text-gs-black-600">{{ $enquiry->created_at?->format('Y-m-d H:i') }}</p>
        </li>

        <li class="rounded border border-gray-200 bg-white p-3">
            <p class="text-sm font-semibold text-gs-black-800">First response</p>
            <p class="text-sm text-gs-black-600">
                {{ $firstResponseAt?->format('Y-m-d H:i') ?? 'Not contacted yet' }}
            </p>
        </li>

        <li class="rounded border border-gray-200 bg-white p-3">
            <p class="text-sm font-semibold text-gs-black-800">Latest reply</p>
            <p class="text-sm text-gs-black-600">
                {{ $latestReplyAt?->format('Y-m-d H:i') ?? 'No replies yet' }}
            </p>
        </li>

        <li class="rounded border border-gray-200 bg-white p-3">
            <p class="text-sm font-semibold text-gs-black-800">Latest note</p>
            <p class="text-sm text-gs-black-600">
                {{ $latestNoteAt?->format('Y-m-d H:i') ?? 'No notes yet' }}
            </p>
        </li>

        <li class="rounded border border-gray-200 bg-white p-3">
            <p class="text-sm font-semibold text-gs-black-800">Closed</p>
            <p class="text-sm text-gs-black-600">
                {{ $enquiry->closed_at?->format('Y-m-d H:i') ?? 'Not closed yet' }}
            </p>
        </li>
    </ol>
</div>
