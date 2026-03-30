@extends('layouts.app')

@section('content')
    <x-ui.card class="max-w-xl">
        <h1 class="mb-2 text-xl font-semibold">Verify Your User Email</h1>
        <p class="mb-4 text-sm text-gs-black-700">Please verify your email address before using sensitive account features.</p>

        <form method="post" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button type="submit">Resend Verification Email</x-ui.button>
        </form>
    </x-ui.card>
@endsection
