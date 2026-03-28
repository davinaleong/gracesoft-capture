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
@endsection
