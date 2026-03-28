@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>Edit Form</h1>

        <form method="post" action="{{ route('manage.forms.update', $form) }}">
            @csrf
            @method('put')

            <div class="grid">
                <div class="full">
                    <label for="name">Form Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $form->name) }}" required>
                </div>

                <div>
                    <label for="account_id">Account ID</label>
                    <input id="account_id" name="account_id" type="text" value="{{ old('account_id', $form->account_id) }}" required>
                </div>

                <div>
                    <label for="application_id">Application ID</label>
                    <input id="application_id" name="application_id" type="text" value="{{ old('application_id', $form->application_id) }}" required>
                </div>

                <div class="full">
                    <label for="notification_email">Notification Email</label>
                    <input id="notification_email" name="notification_email" type="email" value="{{ old('notification_email', data_get($form->settings, 'notification_email')) }}">
                </div>
            </div>

            <div style="margin-top: 1rem;" class="actions">
                <button type="submit">Save Changes</button>
                <a href="{{ route('manage.forms.index') }}">Back to Forms</a>
                <a href="{{ route('forms.show', $form->public_token) }}" target="_blank" rel="noreferrer" class="actions" style="align-items: center; gap: 0.35rem;">
                    <x-icons.eye size="16" />
                    <span>Open Public Form</span>
                </a>
            </div>
        </form>
    </div>
@endsection
