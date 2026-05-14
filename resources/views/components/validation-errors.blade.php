@if ($errors->any())
    <div class="fixed inset-x-0 top-4 z-[60] flex pointer-events-none justify-center px-4 sm:top-6">
        <div class="pointer-events-auto w-full max-w-2xl overflow-hidden rounded-lg border border-red-600 bg-red-50 text-red-950 shadow-2xl" role="alert">
            <div class="h-1.5 bg-red-600"></div>
            <div class="flex gap-4 p-4 sm:p-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-600 text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-base font-semibold">{{ $errors->count() > 1 ? 'Informations a corriger' : 'Information a corriger' }}</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button"
                        class="-mr-2 -mt-2 rounded-md p-2 text-red-800 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                        onclick="this.closest('[role=alert]').remove()"
                        aria-label="Fermer le message">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
@endif
