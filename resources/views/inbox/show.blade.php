@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4 space-y-4">
        <h1 class="text-2xl font-bold">{{ $enquiry->subject }}</h1>

        <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
                <dt class="text-sm font-semibold text-gs-black-700">Name</dt>
                <dd>{{ $enquiry->name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-semibold text-gs-black-700">Email</dt>
                <dd>{{ $enquiry->email }}</dd>
            </div>
            <div>
                <dt class="text-sm font-semibold text-gs-black-700">Status</dt>
                <dd>{{ ucfirst($enquiry->status) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-semibold text-gs-black-700">Form</dt>
                <dd>{{ $enquiry->form?->name }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-sm font-semibold text-gs-black-700">Received</dt>
                <dd>{{ $enquiry->created_at?->format('Y-m-d H:i') }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-sm font-semibold text-gs-black-700">Message</dt>
                <dd class="mt-1 rounded border border-gray-200 bg-gray-50 p-3">{{ $enquiry->message }}</dd>
            </div>
        </dl>
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
