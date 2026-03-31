@props([
    'id' => 'upgrade-modal',
    'title' => 'Upgrade Plan',
    'message' => 'Upgrade your plan to unlock this capability.',
])

<dialog id="{{ $id }}" class="w-full max-w-xl rounded border border-gray-200 p-0 backdrop:bg-black/40">
    <x-ui.card class="space-y-4 border-0 shadow-none">
        <div class="space-y-2">
            <h2 class="text-xl font-bold">{{ $title }}</h2>
            <p class="text-sm text-gs-black-700">{{ $message }}</p>
        </div>

        {{ $slot }}

        <div class="flex justify-end">
            <x-ui.button type="button" variant="neutral" onclick="document.getElementById('{{ $id }}')?.close()">
                Close
            </x-ui.button>
        </div>
    </x-ui.card>
</dialog>
