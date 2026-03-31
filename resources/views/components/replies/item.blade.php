@props([
    'reply',
])

<article class="rounded border border-gray-200 bg-white p-3 space-y-2">
    <div class="flex flex-wrap items-center gap-2">
        <x-replies.sender-badge :sender-type="$reply->sender_type" />

        @if ($reply->is_internal)
            <x-ui.badge variant="neutral">Internal</x-ui.badge>
        @endif

        <span class="text-xs text-gs-black-600">{{ $reply->created_at?->format('Y-m-d H:i') }}</span>
    </div>

    <p class="text-gs-black-800">{{ $reply->content }}</p>
</article>
