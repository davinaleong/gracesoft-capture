@props([
    'enquiry',
    'canReply' => true,
])

<div class="space-y-3">
    @if ($canReply)
        <form method="post" action="{{ route('inbox.replies.store', $enquiry) }}" class="space-y-3">
            @csrf

            <x-ui.field for="reply_content" label="Reply" required>
                <x-ui.textarea id="reply_content" name="content" rows="4" required>{{ old('content') }}</x-ui.textarea>
            </x-ui.field>

            <label class="inline-flex items-center gap-2 text-sm text-gs-black-700" for="reply_internal">
                <input id="reply_internal" type="checkbox" name="is_internal" value="1" @checked((string) old('is_internal') === '1')>
                <span>Mark as internal</span>
            </label>

            <x-ui.button type="submit">Send Reply</x-ui.button>
        </form>
    @else
        <x-ui.alert variant="error">Your role is read-only for replies in this account.</x-ui.alert>
    @endif
</div>
