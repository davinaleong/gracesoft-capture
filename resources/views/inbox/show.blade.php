@extends('layouts.app')

@section('content')
    <div class="card" style="margin-bottom: 1rem;">
        <h1>{{ $enquiry->subject }}</h1>
        <p><strong>Name:</strong> {{ $enquiry->name }}</p>
        <p><strong>Email:</strong> {{ $enquiry->email }}</p>
        <p><strong>Status:</strong> {{ $enquiry->status }}</p>
        <p><strong>Form:</strong> {{ $enquiry->form?->name }}</p>
        <p><strong>Received:</strong> {{ $enquiry->created_at?->format('Y-m-d H:i') }}</p>
        <p><strong>Message:</strong></p>
        <p>{{ $enquiry->message }}</p>
    </div>

    <div class="card">
        <h2>Update Status</h2>
        <div class="actions">
            @if ($enquiry->status === 'new')
                <form method="post" action="{{ route('inbox.status.update', $enquiry) }}">
                    @csrf
                    <input type="hidden" name="status" value="contacted">
                    <button type="submit" class="actions" style="align-items: center; gap: 0.35rem;">
                        <x-icons.pencil size="16" />
                        <span>Mark Contacted</span>
                    </button>
                </form>
            @endif

            @if ($enquiry->status === 'contacted')
                <form method="post" action="{{ route('inbox.status.update', $enquiry) }}">
                    @csrf
                    <input type="hidden" name="status" value="closed">
                    <button type="submit" class="actions" style="align-items: center; gap: 0.35rem;">
                        <x-icons.trash size="16" />
                        <span>Mark Closed</span>
                    </button>
                </form>
            @endif

            <a href="{{ route('inbox.index') }}">Back to Inbox</a>
        </div>
    </div>

    <div class="card" style="margin-top: 1rem;">
        <h2>Notes</h2>

        @if ($notesEnabled)
            <form method="post" action="{{ route('inbox.notes.store', $enquiry) }}">
                @csrf
                <div class="grid">
                    <div class="full">
                        <label for="user_id">User ID (HQ)</label>
                        <input id="user_id" name="user_id" type="text" value="{{ old('user_id') }}" required>
                    </div>

                    <div class="full">
                        <label for="content">Note</label>
                        <textarea id="content" name="content" rows="4" required>{{ old('content') }}</textarea>
                    </div>
                </div>

                <div style="margin-top: 1rem;" class="actions">
                    <button type="submit">Add Note</button>
                </div>
            </form>
        @else
            <p>Notes are available on the Pro plan only.</p>
        @endif

        <hr style="margin: 1rem 0; border: 0; border-top: 1px solid #dbe3ec;">

        @forelse ($enquiry->notes as $note)
            <article style="padding: 0.75rem; border: 1px solid #dbe3ec; border-radius: 8px; margin-bottom: 0.75rem;">
                <p style="margin: 0 0 0.35rem;"><strong>User:</strong> {{ $note->user_id }}</p>
                <p style="margin: 0 0 0.35rem;"><strong>Added:</strong> {{ $note->created_at?->format('Y-m-d H:i') }}</p>
                <p style="margin: 0;">{{ $note->content }}</p>
            </article>
        @empty
            <p>No notes yet.</p>
        @endforelse
    </div>
@endsection
