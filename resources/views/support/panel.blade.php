@extends('layouts.app')

@section('content')
    <x-ui.card class="space-y-4">
        <h1 class="text-2xl font-bold">Contact Support</h1>
        <p class="text-gs-black-700">Send your issue or question and we will forward it to HQ support.</p>

        <p class="text-sm text-gs-black-600">
            This support form is scoped for your authenticated workspace session.
        </p>

        <x-feedback.button target="panel-support-feedback-modal" label="Open Support Form" />

        <x-feedback.modal id="panel-support-feedback-modal" title="Contact Support">
            <x-feedback.form :action="route('panel.support.store')" />
        </x-feedback.modal>

        <noscript>
            <x-feedback.form :action="route('panel.support.store')" />
        </noscript>
    </x-ui.card>
@endsection
