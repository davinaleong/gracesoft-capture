@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>Create Form</h1>

        <form method="post" action="{{ route('manage.forms.store') }}">
            @csrf

            <div class="grid">
                <div class="full">
                    <label for="name">Form Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                </div>

                <div>
                    <label for="account_id">Account ID</label>
                    <input id="account_id" name="account_id" type="text" value="{{ old('account_id') }}" required>
                </div>

                <div>
                    <label for="application_id">Application ID</label>
                    <input id="application_id" name="application_id" type="text" value="{{ old('application_id') }}" required>
                </div>

                <div class="full">
                    <label for="notification_email">Notification Email</label>
                    <input id="notification_email" name="notification_email" type="email" value="{{ old('notification_email') }}">
                </div>
            </div>

            <div style="margin-top: 1rem;" class="actions">
                <button type="submit">Create Form</button>
                <a href="{{ route('manage.forms.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
