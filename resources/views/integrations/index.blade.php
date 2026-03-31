@extends('layouts.app')

@section('content')
    <x-ui.card class="mb-4">
        <h1 class="text-xl font-semibold text-gs-black-800">Integration</h1>
        <p class="mt-1 text-sm text-gs-black-600">Embed your form in any website using the iframe snippet below.</p>
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
                        <td colspan="4" class="p-4 text-center text-gs-black-600">No forms available for integration yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $forms->links() }}
        </div>
    </x-ui.card>
@endsection
