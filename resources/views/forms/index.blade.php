@extends('layouts.app')

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.button tag="a" href="{{ route('manage.forms.create') }}">Create Form</x-ui.button>
    </div>

    <x-ui.card>
        <x-ui.table>
            <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                <tr>
                    <th class="p-2">Name</th>
                    <th class="p-2">Token</th>
                    <th class="p-2">Account</th>
                    <th class="p-2">Application</th>
                    <th class="p-2">Active</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <tr class="border-b border-gray-200">
                        <td class="p-2">{{ $form->name }}</td>
                        <td class="p-2">{{ $form->public_token }}</td>
                        <td class="p-2">{{ $form->account_id }}</td>
                        <td class="p-2">{{ $form->application_id }}</td>
                        <td class="p-2">
                            <x-ui.badge :variant="$form->is_active ? 'success' : 'neutral'">
                                {{ $form->is_active ? 'Active' : 'Inactive' }}
                            </x-ui.badge>
                        </td>
                        <td class="p-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.button tag="a" href="{{ route('manage.forms.edit', $form) }}" variant="secondary" size="sm">Edit</x-ui.button>
                                <form method="post" action="{{ route('manage.forms.toggle-active', $form) }}" class="inline-flex">
                                    @csrf
                                    <x-ui.button type="submit" :variant="$form->is_active ? 'danger' : 'success'" size="sm">
                                        {{ $form->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-ui.button>
                                </form>
                                <x-ui.button tag="a" href="{{ route('forms.show', $form->public_token) }}" target="_blank" rel="noreferrer" variant="secondary" size="sm">
                                    <x-icons.eye size="16" />
                                    <span>Open Form</span>
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gs-black-600">No forms created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $forms->links() }}
        </div>
    </x-ui.card>
@endsection
