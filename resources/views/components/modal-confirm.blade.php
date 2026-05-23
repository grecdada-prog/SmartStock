@props(['id', 'title', 'message', 'confirmText' => 'Confirmer', 'cancelText' => 'Annuler', 'type' => 'danger'])

@php
    $typeClasses = [
        'danger' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
        'warning' => 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
        'success' => 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
        'info' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
    ];

    $iconClasses = [
        'danger' => 'bg-red-100 text-red-600',
        'warning' => 'bg-yellow-100 text-yellow-600',
        'success' => 'bg-rose-100 text-rose-600',
        'info' => 'bg-blue-100 text-blue-600',
    ];

    $icons = [
        'danger' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ];
@endphp

<div x-data="{ show: false }"
     x-on:open-modal-{{ $id }}.window="show = true"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true"
     style="display: none;">

    <!-- Background overlay -->
    <div class="flex min-h-screen items-center justify-center px-4 py-6 text-center">
        <div x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/50 transition-opacity"
             aria-hidden="true"
             @click="show = false"></div>

        <div x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative flex max-h-[calc(100vh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white text-left shadow-xl transform transition-all">

            <div class="shrink-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900" id="modal-title">
                    {{ $title }}
                </h3>
                <button type="button"
                        @click="show = false"
                        class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                    <span class="sr-only">Fermer</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full {{ $iconClasses[$type] }} sm:mx-0">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            {!! $icons[$type] !!}
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <div>
                            <p class="text-sm text-gray-500">
                                {{ $message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="shrink-0 border-t border-gray-200 bg-white px-6 py-4 sm:flex sm:flex-row-reverse">
                <button type="button"
                        @click="$dispatch('modal-confirmed-{{ $id }}'); show = false"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 {{ $typeClasses[$type] }} text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto transition-colors duration-200">
                    {{ $confirmText }}
                </button>
                <button type="button"
                        @click="show = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 sm:mt-0 sm:w-auto transition-colors duration-200">
                    {{ $cancelText }}
                </button>
            </div>
        </div>
    </div>
</div>
