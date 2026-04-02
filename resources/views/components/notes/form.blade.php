@props(['enquiry'])

<form method="post" action="{{ route('inbox.notes.store', $enquiry) }}" class="space-y-4">
    @csrf

    <input type="hidden" id="user_id" name="user_id" value="{{ old('user_id') }}">

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-ui.field for="visibility" label="Visibility">
            <x-ui.select id="visibility" name="visibility">
                <option value="internal" @selected(old('visibility', 'internal') === 'internal')>Internal</option>
                <option value="external" @selected(old('visibility') === 'external')>External</option>
            </x-ui.select>
        </x-ui.field>
    </div>

    <x-ui.field for="content" label="Note" required>
        <x-ui.textarea id="content" name="content" rows="4" required>{{ old('content') }}</x-ui.textarea>
    </x-ui.field>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-ui.field for="tags" label="Tags">
            <x-ui.input
                id="tags"
                name="tags"
                :value="old('tags')"
                placeholder="follow-up, priority"
            />
        </x-ui.field>

        <x-ui.field for="reminder_at" label="Reminder Date">
            <x-ui.input id="reminder_at" name="reminder_at" type="date" :value="old('reminder_at')" />
        </x-ui.field>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-gs-black-700">
        <input
            type="checkbox"
            name="is_pinned"
            value="1"
            class="rounded border-gray-300 text-gs-purple-600 focus:ring-gs-purple-300"
            @checked(old('is_pinned'))
        >
        <span>Pin this note</span>
    </label>

    <div class="flex items-center gap-2">
        <x-ui.button type="submit">Add Note</x-ui.button>
    </div>
</form>
