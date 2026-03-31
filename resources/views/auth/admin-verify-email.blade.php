@extends('layouts.auth')

@section('content')
    <x-ui.card class="max-w-xl">
        <h1 class="mb-2 text-xl font-semibold">Verify Administrator Email</h1>
        <p class="mb-4 text-sm text-gs-black-700">Your administrator identity must be verified to continue using compliance actions.</p>

        <form method="post" action="{{ route('admin.verification.send') }}">
            @csrf
            <x-ui.button type="submit" variant="danger">Resend Admin Verification Email</x-ui.button>
        </form>
    </x-ui.card>
@endsection
