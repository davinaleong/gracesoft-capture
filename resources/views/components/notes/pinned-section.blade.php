@props(['notes'])

@php
    $pinnedNotes = $notes->filter(fn ($note) => (bool) $note->is_pinned);
@endphp

@if ($pinnedNotes->isNotEmpty())
    <section class="space-y-2">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gs-black-600">Pinned Notes</h3>

        @foreach ($pinnedNotes as $note)
            <x-notes.item :note="$note" />
        @endforeach
    </section>
@endif
