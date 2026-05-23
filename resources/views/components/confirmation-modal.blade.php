@props(['id', 'title', 'message', 'confirmText' => 'Confirmer', 'cancelText' => 'Annuler'])

<div x-data="{ show: false }" 
     @open-modal.window="if ($event.detail.id === '{{ $id }}') { show = true }"
     @close-modal.window="show = false"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50"
     style="display: none;">
    
    <!-- Overlay -->
    <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="show = false"></div>

    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center px-4 py-6">
        <div @click.away="show = false" 
             class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-lg bg-white shadow-xl transform transition-all sm:max-w-lg sm:w-full"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            <div class="shrink-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $title }}
                </h3>
                <button type="button" @click="show = false" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                    <span class="sr-only">Fermer</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto bg-white px-6 py-5">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-red-100 sm:mx-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
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
                        @click="$dispatch('confirm-action', { id: '{{ $id }}' }); show = false"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ $confirmText }}
                </button>
                <button type="button"
                        @click="show = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ $cancelText }}
                </button>
            </div>
        </div>
    </div>
</div>
