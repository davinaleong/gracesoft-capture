@props(['notes'])

@php
    $sorted = $notes
        ->sortByDesc(fn ($note) => (bool) $note->is_pinned)
        ->sortByDesc('created_at')
        ->values();

    $unpinned = $sorted->filter(fn ($note) => ! $note->is_pinned)->values();
@endphp

@if ($sorted->isEmpty())
    <p class="text-gs-black-600">No notes yet.</p>
@else
    <div class="space-y-3">
        <x-notes.pinned-section :notes="$sorted" />

        @if ($unpinned->isNotEmpty())
            <section class="space-y-2">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gs-black-600">Recent Notes</h3>

                @foreach ($unpinned as $note)
                    <x-notes.item :note="$note" />
                @endforeach
            </section>
        @endif
    </div>
@endif
