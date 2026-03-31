@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4 space-y-4">
        <x-enquiry.header :enquiry="$enquiry" />
        <x-enquiry.message-card :enquiry="$enquiry" />
        <x-enquiry.timeline :enquiry="$enquiry" />
    </x-ui.card>

    <x-ui.card class="mb-4 space-y-4">
        <h2 class="text-xl font-bold">Replies</h2>

        <x-replies.form :enquiry="$enquiry" :can-reply="$canReply" />

        <hr class="border-gray-200">

        <x-replies.list :replies="$enquiry->replies" />
    </x-ui.card>

    <x-ui.card class="space-y-3">
        <h2 class="text-xl font-bold">Update Status</h2>
        <div class="flex flex-wrap items-center gap-2">
            @if ($enquiry->status === 'new')
                <form method="post" action="{{ route('inbox.status.update', $enquiry) }}">
                    @csrf
                    <input type="hidden" name="status" value="contacted">
                    <x-ui.button type="submit" variant="secondary">
                        <x-icons.pencil size="16" />
                        <span>Mark Contacted</span>
                    </x-ui.button>
                </form>
            @endif

            @if ($enquiry->status === 'contacted')
                <form method="post" action="{{ route('inbox.status.update', $enquiry) }}">
                    @csrf
                    <input type="hidden" name="status" value="closed">
                    <x-ui.button type="submit" variant="danger">
                        <x-icons.trash size="16" />
                        <span>Mark Closed</span>
                    </x-ui.button>
                </form>
            @endif

            <x-ui.button tag="a" href="{{ route('inbox.index') }}" variant="secondary">Back to Inbox</x-ui.button>
        </div>
    </x-ui.card>

    <x-ui.card class="mt-4 space-y-4">
        <h2 class="text-xl font-bold">Notes</h2>

        @if ($notesEnabled)
            <form method="post" action="{{ route('inbox.notes.store', $enquiry) }}">
                @csrf
                <div class="grid grid-cols-1 gap-4">
                    <x-ui.field for="user_id" label="User ID (HQ)" required>
                        <x-ui.input id="user_id" name="user_id" :value="old('user_id')" required />
                    </x-ui.field>

                    <x-ui.field for="content" label="Note" required>
                        <x-ui.textarea id="content" name="content" rows="4" required>{{ old('content') }}</x-ui.textarea>
                    </x-ui.field>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <x-ui.button type="submit">Add Note</x-ui.button>
                </div>
            </form>
        @else
            <x-ui.alert variant="info">Notes are available on the Pro plan only.</x-ui.alert>
        @endif

        <hr class="border-gray-200">

        @forelse ($enquiry->notes as $note)
            <article class="rounded border border-gray-200 bg-white p-3">
                <p class="mb-1"><strong>User:</strong> {{ $note->user_id }}</p>
                <p class="mb-1"><strong>Added:</strong> {{ $note->created_at?->format('Y-m-d H:i') }}</p>
                <p>{{ $note->content }}</p>
            </article>
        @empty
            <p class="text-gs-black-600">No notes yet.</p>
        @endforelse
    </x-ui.card>
@endsection
