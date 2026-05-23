@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-md bg-[#e80033] px-3 py-1.5 text-start text-sm font-bold text-white transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#e80033] focus:ring-offset-2'
            : 'block w-full rounded-md px-3 py-1.5 text-start text-sm font-semibold text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-[#e80033] focus:ring-offset-2';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
