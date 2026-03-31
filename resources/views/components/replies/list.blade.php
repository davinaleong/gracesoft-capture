@props([
    'replies',
])

<div class="space-y-3">
    @forelse ($replies as $reply)
        <x-replies.item :reply="$reply" />
    @empty
        <x-replies.empty-state />
    @endforelse
</div>
