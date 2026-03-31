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

        @if ($notesEnabled && $canManageNotes)
            <x-notes.form :enquiry="$enquiry" />
        @elseif ($notesEnabled)
            <x-enquiry.access-denied-state message="Your role is read-only for notes in this account." />
        @else
            <x-notes.upgrade-banner />
        @endif

        <hr class="border-gray-200">

        <x-notes.list :notes="$enquiry->notes" />
    </x-ui.card>
@endsection
