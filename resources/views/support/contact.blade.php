@extends('layouts.auth')

@section('content')
    <x-ui.card class="space-y-4">
        <h1 class="text-2xl font-bold">Contact Support</h1>
        <p class="text-gs-black-700">Send your issue or question and we will forward it to HQ support.</p>

        <p class="text-sm text-gs-black-600">
            New to the product? Sign in and follow the guided walkthrough cards in Forms, Integrations, and Inbox.
        </p>

        <x-feedback.button target="support-feedback-modal" label="Open Support Form" />

        <x-feedback.modal id="support-feedback-modal" title="Contact Support">
            <x-feedback.form />
        </x-feedback.modal>

        <noscript>
            <x-feedback.form />
        </noscript>

        <div class="border-t border-gs-black-100 pt-3 text-xs text-gs-black-600">
            <a href="{{ route('legal.privacy') }}" class="underline decoration-gs-black-300 underline-offset-2 hover:text-gs-black-800">Privacy Policy</a>
            <span class="mx-2">|</span>
            <a href="{{ route('legal.terms') }}" class="underline decoration-gs-black-300 underline-offset-2 hover:text-gs-black-800">Terms and Conditions</a>
        </div>
    </x-ui.card>
@endsection
