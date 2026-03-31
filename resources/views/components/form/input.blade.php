@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
])

<x-ui.field :for="$id" :label="$label" :required="$required" {{ $attributes->only('class') }}>
    <x-ui.input
        :type="$type"
        :id="$id"
        :name="$name"
        :value="$value"
        :required="$required"
        :autocomplete="$autocomplete"
    />
</x-ui.field>
