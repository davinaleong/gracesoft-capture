@extends('layouts.app')

@section('content')
    <div class="card" style="margin-bottom: 1rem;">
        <form method="get" action="{{ route('inbox.index') }}" class="actions">
            <label for="status" style="margin: 0;">Filter Status</label>
            <select id="status" name="status" style="width: 180px;">
                <option value="" @selected($selectedStatus === '')>All</option>
                <option value="new" @selected($selectedStatus === 'new')>New</option>
                <option value="contacted" @selected($selectedStatus === 'contacted')>Contacted</option>
                <option value="closed" @selected($selectedStatus === 'closed')>Closed</option>
            </select>
            <button type="submit">Apply</button>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Form</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enquiries as $enquiry)
                    <tr>
                        <td>{{ $enquiry->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $enquiry->name }}</td>
                        <td>{{ $enquiry->email }}</td>
                        <td>{{ $enquiry->subject }}</td>
                        <td>{{ $enquiry->status }}</td>
                        <td>{{ $enquiry->form?->name }}</td>
                        <td>
                            <a href="{{ route('inbox.show', $enquiry) }}" class="actions" style="align-items: center; gap: 0.35rem;">
                                <x-icons.eye size="16" />
                                <span>View</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No enquiries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 1rem;">
            {{ $enquiries->links() }}
        </div>
    </div>
@endsection
