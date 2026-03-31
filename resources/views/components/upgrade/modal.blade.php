@props([
    'id' => 'upgrade-modal',
    'title' => 'Upgrade Plan',
    'message' => 'Upgrade your plan to unlock this capability.',
])

<x-ui.modal :id="$id" :title="$title" max-width="max-w-xl">
    <p class="text-sm text-gs-black-700">{{ $message }}</p>

    {{ $slot }}

    <div class="flex justify-end">
        <x-ui.button type="button" variant="neutral" onclick="document.getElementById('{{ $id }}')?.close()">
            Close
        </x-ui.button>
    </div>
</x-ui.modal>
