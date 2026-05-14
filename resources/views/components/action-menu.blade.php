@props(['label' => 'Action'])

<x-dropdown align="right" width="48" contentClasses="p-1 bg-white" dropdownClasses="z-[80]">
    <x-slot name="trigger">
        <button type="button" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
            {{ $label }}
            <svg class="h-4 w-4 text-gray-500 transition" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-0.5">
            {{ $slot }}
        </div>
    </x-slot>
</x-dropdown>
