<tr>
    <td colspan="7" class="p-6 text-center text-gs-black-600">
        <p class="font-semibold text-gs-black-800">No enquiries found.</p>
        <p class="mt-1 text-sm">When visitors submit your public form, new enquiries will appear here.</p>
        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
            <x-ui.button tag="a" href="{{ route('manage.forms.create') }}" size="sm" class="px-4">Create Form</x-ui.button>
            <x-ui.button tag="a" href="{{ route('integrations.index') }}" variant="secondary" size="sm">Open Integrations</x-ui.button>
        </div>
    </td>
</tr>
