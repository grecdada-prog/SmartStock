@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-md border border-transparent bg-[#e80033] px-3 py-1.5 text-sm font-bold leading-5 text-white shadow-sm transition duration-150 ease-in-out hover:bg-[#d4002e] focus:outline-none focus:ring-2 focus:ring-[#e80033] focus:ring-offset-2'
            : 'inline-flex items-center rounded-md border border-transparent px-2.5 py-1.5 text-sm font-semibold leading-5 text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-[#e80033] focus:ring-offset-2';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
