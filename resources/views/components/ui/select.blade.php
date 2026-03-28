@props([
    'id' => null,
    'name' => null,
])

<select
    @if($id)id="{{ $id }}"@endif
    @if($name)name="{{ $name }}"@endif
    {{ $attributes->class(['w-full block bg-gs-black-50 rounded p-2 border border-gray-300 focus:border-gs-purple-400 focus:ring-2 focus:ring-gs-purple-200 outline-none']) }}
>
    {{ $slot }}
</select>
