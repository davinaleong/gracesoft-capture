@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4">
        <div class="mb-3">
            <x-inbox.account-context-badge :account-id="$accountId" />
        </div>

        <x-inbox.filters :selected-status="$selectedStatus" :search="$search" />
    </x-ui.card>

    <x-ui.card>
        <x-inbox.table :enquiries="$enquiries" />

        <div class="mt-4">
            {{ $enquiries->links() }}
        </div>
    </x-ui.card>
@endsection
