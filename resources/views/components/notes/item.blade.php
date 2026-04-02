@props(['note'])

<article class="rounded border border-gray-200 bg-white p-3 space-y-2">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <x-notes.visibility-badge :visibility="$note->visibility ?? 'internal'" />

            @if ($note->is_pinned)
                <span class="inline-flex items-center rounded border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-700">
                    Pinned
                </span>
            @endif
        </div>

        <p class="text-xs text-gs-black-600">{{ $note->created_at?->format('Y-m-d H:i') }}</p>
    </div>

    <p class="text-sm text-gs-black-700"><strong>Created by:</strong> {{ $note->creator_name ?: 'System' }}</p>
    <p class="text-sm text-gs-black-900 whitespace-pre-line">{{ $note->content }}</p>

    @if (! empty($note->tags))
        <div class="flex flex-wrap items-center gap-1">
            @foreach ($note->tags as $tag)
                <span class="inline-flex items-center rounded-full bg-gs-purple-50 px-2 py-0.5 text-xs font-medium text-gs-purple-700">
                    #{{ $tag }}
                </span>
            @endforeach
        </div>
    @endif

    @if ($note->reminder_at)
        <p class="text-xs text-gs-black-600">
            Reminder: {{ $note->reminder_at->format('Y-m-d') }}
        </p>
    @endif
</article>
