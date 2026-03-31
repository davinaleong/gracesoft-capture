@props([
    'enquiries',
])

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
            <x-inbox.row :enquiry="$enquiry" />
        @empty
            <x-inbox.empty-state />
        @endforelse
    </tbody>
</x-ui.table>
