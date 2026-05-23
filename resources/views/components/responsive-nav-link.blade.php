@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 border-[#e80033] bg-red-50 px-3 py-2 text-start text-sm font-bold text-[#e80033] transition duration-150 ease-in-out focus:outline-none'
            : 'block w-full border-l-4 border-transparent px-3 py-2 text-start text-sm font-semibold text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-950 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
