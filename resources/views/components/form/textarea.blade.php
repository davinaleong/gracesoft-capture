@props([
    'id',
    'name',
    'label',
    'value' => null,
    'rows' => 5,
    'required' => false,
])

<x-ui.field :for="$id" :label="$label" :required="$required" {{ $attributes->only('class') }}>
    <x-ui.textarea :id="$id" :name="$name" :rows="$rows" :required="$required">{{ $value }}</x-ui.textarea>
</x-ui.field>
