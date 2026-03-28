@props([
    'for' => null,
    'label' => null,
    'required' => false,
])

<div {{ $attributes->class(['space-y-1']) }}>
    @if ($label)
        <label @if($for)for="{{ $for }}"@endif class="block text-sm font-semibold text-gs-black-800">
            {{ $label }}@if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    {{ $slot }}
</div>
