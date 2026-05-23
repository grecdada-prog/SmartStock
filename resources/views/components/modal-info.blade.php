@props(['id', 'title'])

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
    <div class="flex min-h-screen items-center justify-center px-4 py-6">
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
             class="relative flex max-h-[calc(100vh-3rem)] w-full max-w-2xl flex-col overflow-hidden rounded-lg bg-white text-left shadow-xl transform transition-all">

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
                {{ $slot }}
            </div>

            <div class="shrink-0 border-t border-gray-200 bg-white px-6 py-4">
                <button type="button"
                        @click="show = false"
                        class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>
