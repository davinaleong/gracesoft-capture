@props([
    'id' => null,
    'name' => null,
    'rows' => 4,
])

<textarea
    @if($id)id="{{ $id }}"@endif
    @if($name)name="{{ $name }}"@endif
    rows="{{ $rows }}"
    {{ $attributes->class(['w-full block bg-gs-black-50 rounded p-2 border border-gray-300 focus:border-gs-purple-400 focus:ring-2 focus:ring-gs-purple-200 outline-none']) }}
>{{ $slot }}</textarea>
