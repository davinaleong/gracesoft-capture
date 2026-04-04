@props([
    'form',
    'appDomain' => '',
])

@php
    $embedCode = '<iframe src="' . route('forms.show', ['token' => $form->public_token, 'surface' => 'none']) . '" width="100%" height="600" style="display:block;width:100%;max-width:100%;border:0;background:transparent;"></iframe>';
@endphp

<tr class="border-b border-gray-200 align-top">
    <td class="p-2">
        <p class="font-semibold text-gs-black-800">{{ $form->name }}</p>
    </td>
    <td class="p-2 text-sm text-gs-black-700">{{ $appDomain !== '' ? $appDomain : 'N/A' }}</td>
    <td class="p-2">
        <x-integration.embed-code :code="$embedCode" :token="$form->public_token" />
    </td>
    <td class="p-2">
        <x-integration.test-button :token="$form->public_token" />
    </td>
</tr>
