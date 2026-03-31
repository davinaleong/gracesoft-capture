@props([
    'id' => 'feedback-modal',
    'title' => 'Contact Support',
])

<x-ui.modal :id="$id" :title="$title" max-width="max-w-3xl">
    <div class="flex justify-end">
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
</x-ui.modal>
