@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4">
        <form method="get" action="{{ route('inbox.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,200px)_auto] md:items-end">
            <x-ui.field for="status" label="Filter Status">
                <x-ui.select id="status" name="status">
                    <option value="" @selected($selectedStatus === '')>All</option>
                    <option value="new" @selected($selectedStatus === 'new')>New</option>
                    <option value="contacted" @selected($selectedStatus === 'contacted')>Contacted</option>
                    <option value="closed" @selected($selectedStatus === 'closed')>Closed</option>
                </x-ui.select>
            </x-ui.field>

            <div class="flex items-center gap-2">
                <x-ui.button type="submit">Apply</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card>
        <x-ui.table>
            <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                <tr>
                    <th class="p-2">Date</th>
                    <th class="p-2">Name</th>
                    <th class="p-2">Email</th>
                    <th class="p-2">Subject</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Form</th>
                    <th class="p-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enquiries as $enquiry)
                    @php
                        $statusVariant = match ($enquiry->status) {
                            'new' => 'info',
                            'contacted' => 'primary',
                            'closed' => 'success',
                            default => 'neutral',
                        };
                    @endphp
                    <tr class="border-b border-gray-200">
                        <td class="p-2">{{ $enquiry->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="p-2">{{ $enquiry->name }}</td>
                        <td class="p-2">{{ $enquiry->email }}</td>
                        <td class="p-2">{{ $enquiry->subject }}</td>
                        <td class="p-2"><x-ui.badge :variant="$statusVariant">{{ ucfirst($enquiry->status) }}</x-ui.badge></td>
                        <td class="p-2">{{ $enquiry->form?->name }}</td>
                        <td class="p-2">
                            <x-ui.button tag="a" href="{{ route('inbox.show', $enquiry) }}" variant="secondary" size="sm">
                                <x-icons.eye size="16" />
                                <span>View</span>
                            </x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gs-black-600">No enquiries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $enquiries->links() }}
        </div>
    </x-ui.card>
@endsection
