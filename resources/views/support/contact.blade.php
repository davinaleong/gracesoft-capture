@extends('layouts.app')

@section('content')
    <x-ui.card class="space-y-4">
        <h1 class="text-2xl font-bold">Contact Support</h1>
        <p class="text-gs-black-700">Send your issue or question and we will forward it to HQ support.</p>

        <x-feedback.button target="support-feedback-modal" label="Open Support Form" />

        <x-feedback.modal id="support-feedback-modal" title="Contact Support">
            <x-feedback.form />
        </x-feedback.modal>

        <noscript>
            <x-feedback.form />
        </noscript>
    </x-ui.card>
@endsection
