@props([
    'enquiry',
])

<tr class="border-b border-gray-200">
    <td class="p-2">{{ $enquiry->created_at?->format('Y-m-d H:i') }}</td>
    <td class="p-2">{{ $enquiry->name }}</td>
    <td class="p-2">{{ $enquiry->email }}</td>
    <td class="p-2">{{ $enquiry->subject }}</td>
    <td class="p-2"><x-inbox.status-badge :status="$enquiry->status" /></td>
    <td class="p-2">{{ $enquiry->form?->name }}</td>
    <td class="p-2">
        <x-ui.button tag="a" href="{{ route('inbox.show', $enquiry) }}" variant="secondary" size="sm">
            <x-icons.eye size="16" />
            <span>View</span>
        </x-ui.button>
    </td>
</tr>
