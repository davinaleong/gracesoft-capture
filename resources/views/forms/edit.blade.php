@extends('layouts.app')

@section('content')
    <x-ui.card class="space-y-4">
        <h1 class="text-2xl font-bold">Edit Form</h1>

        <form method="post" action="{{ route('manage.forms.update', $form) }}">
            @csrf
            @method('put')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.field for="name" label="Form Name" required class="md:col-span-2">
                    <x-ui.input id="name" name="name" :value="old('name', $form->name)" required />
                </x-ui.field>

                <x-ui.field for="application_id" label="Application ID" required>
                    <x-ui.input id="application_id" name="application_id" :value="old('application_id', $form->application_id)" required />
                </x-ui.field>

                <x-ui.field for="notification_email" label="Notification Email" class="md:col-span-2">
                    <x-ui.input id="notification_email" name="notification_email" type="email" :value="old('notification_email', data_get($form->settings, 'notification_email'))" />
                </x-ui.field>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <x-ui.button tag="a" href="{{ route('manage.forms.index') }}" variant="secondary">Back to Forms</x-ui.button>
                <x-ui.button tag="a" href="{{ route('integrations.index', ['account_id' => $form->account_id, 'form_id' => $form->uuid]) }}" variant="secondary">Integrate</x-ui.button>
                <x-ui.button tag="a" href="{{ route('forms.show', $form->public_token) }}" variant="secondary" target="_blank" rel="noreferrer">
                    <x-icons.eye size="16" />
                    <span>Open Public Form</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
