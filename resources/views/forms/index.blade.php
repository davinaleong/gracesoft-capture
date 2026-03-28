@extends('layouts.app')

@section('content')
    <div class="actions" style="margin-bottom: 1rem;">
        <a href="{{ route('manage.forms.create') }}">Create Form</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Token</th>
                    <th>Account</th>
                    <th>Application</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <tr>
                        <td>{{ $form->name }}</td>
                        <td>{{ $form->public_token }}</td>
                        <td>{{ $form->account_id }}</td>
                        <td>{{ $form->application_id }}</td>
                        <td>{{ $form->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('manage.forms.edit', $form) }}">Edit</a>
                                <form method="post" action="{{ route('manage.forms.toggle-active', $form) }}">
                                    @csrf
                                    <button type="submit">{{ $form->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                                <a href="{{ route('forms.show', $form->public_token) }}" target="_blank" rel="noreferrer" class="actions" style="align-items: center; gap: 0.35rem;">
                                    <x-icons.eye size="16" />
                                    <span>Open Form</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No forms created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 1rem;">
            {{ $forms->links() }}
        </div>
    </div>
@endsection
