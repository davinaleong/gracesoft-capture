@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>Contact Support</h1>
        <p style="color: #475569; margin-top: 0;">Send your issue or question and we will forward it to HQ support.</p>

        <form method="post" action="{{ route('support.store') }}">
            @csrf

            <div class="grid">
                <div>
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                </div>

                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>

                <div class="full">
                    <label for="subject">Subject</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required>
                </div>

                <div class="full">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
                </div>

                <div class="full">
                    <label for="account_id">Account ID (optional)</label>
                    <input id="account_id" name="account_id" type="text" value="{{ old('account_id') }}">
                </div>
            </div>

            <div class="actions" style="margin-top: 1rem;">
                <button type="submit">Send to Support</button>
            </div>
        </form>
    </div>
@endsection
