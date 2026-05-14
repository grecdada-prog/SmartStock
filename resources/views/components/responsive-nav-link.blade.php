@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-rose-600 text-start text-base font-medium text-rose-800 bg-rose-50 focus:outline-none focus:text-rose-900 focus:bg-rose-100 focus:border-rose-700 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-rose-50 hover:border-rose-300 focus:outline-none focus:text-gray-900 focus:bg-rose-50 focus:border-rose-400 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
