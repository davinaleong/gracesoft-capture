@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4">
        <h1 class="text-xl font-semibold text-gs-black-800">Integration</h1>
        <p class="mt-1 text-sm text-gs-black-600">Embed your form in any website using the iframe snippet below.</p>
        @if (! is_null($selectedFormId ?? null))
            <p class="mt-2 text-xs text-gs-purple-700">Showing embed code for the selected form.</p>
        @endif
    </x-ui.card>

    <x-ui.card>
        <x-ui.table>
            <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                <tr>
                    <th class="p-2">Form Name</th>
                    <th class="p-2">Domain</th>
                    <th class="p-2">Embed Code</th>
                    <th class="p-2">Test</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <x-integration.card :form="$form" :app-domain="$appDomain" />
                @empty
                    <tr>
                        <td colspan="4" class="p-6 text-center text-gs-black-600">
                            <p class="font-semibold text-gs-black-800">No forms available for integration yet.</p>
                            <p class="mt-1 text-sm">Complete setup from the sidebar, then return here to publish your embed snippet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $forms->links() }}
        </div>
    </x-ui.card>
@endsection
