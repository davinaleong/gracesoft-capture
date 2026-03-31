@props([
    'id' => 'feedback-modal',
    'title' => 'Contact Support',
])

<dialog id="{{ $id }}" class="w-full max-w-3xl rounded border border-gray-200 p-0 backdrop:bg-black/40">
    <x-ui.card class="space-y-4 border-0 shadow-none">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-bold">{{ $title }}</h2>

            <x-ui.button
                type="button"
                variant="neutral"
                size="sm"
                onclick="document.getElementById('{{ $id }}')?.close()"
            >
                Close
            </x-ui.button>
        </div>

        {{ $slot }}
    </x-ui.card>
</dialog>
