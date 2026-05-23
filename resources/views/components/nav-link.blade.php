@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-[#e80033] px-3 py-1.5 text-sm font-bold leading-5 text-[#e80033] transition duration-150 ease-in-out hover:text-gray-950 focus:outline-none'
            : 'inline-flex items-center border-b-2 border-transparent px-3 py-1.5 text-sm font-semibold leading-5 text-gray-600 transition duration-150 ease-in-out hover:text-gray-950 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
