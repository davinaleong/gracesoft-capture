@props([
    'id' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
])

<input
    @if($id)id="{{ $id }}"@endif
    @if($name)name="{{ $name }}"@endif
    type="{{ $type }}"
    @if(! is_null($value))value="{{ $value }}"@endif
    {{ $attributes->class(['w-full block h-10 bg-gs-black-50 rounded px-3 border border-gray-300 focus:border-gs-purple-400 focus:ring-2 focus:ring-gs-purple-200 outline-none']) }}
/>
