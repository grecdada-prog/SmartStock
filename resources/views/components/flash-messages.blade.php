@php
    $messages = collect([
        'success' => session('success'),
        'error' => session('error') ?? ($errors->any() ? $errors->first() : null),
        'warning' => session('warning'),
        'info' => session('info') ?? session('status') ?? session('message'),
    ])->filter();

    $toneClasses = [
        'success' => [
            'border' => 'border-rose-300',
            'icon' => 'text-rose-600',
            'focus' => 'focus:ring-rose-600',
        ],
        'error' => [
            'border' => 'border-red-300',
            'icon' => 'text-red-600',
            'focus' => 'focus:ring-red-600',
        ],
        'warning' => [
            'border' => 'border-amber-300',
            'icon' => 'text-amber-600',
            'focus' => 'focus:ring-amber-600',
        ],
        'info' => [
            'border' => 'border-blue-300',
            'icon' => 'text-blue-600',
            'focus' => 'focus:ring-blue-600',
        ],
    ];
@endphp

@if ($messages->isNotEmpty())
    <div class="pointer-events-none fixed left-1/2 top-3 z-[9999] flex w-[calc(100%-1.5rem)] max-w-md -translate-x-1/2 flex-col gap-3">
        @foreach ($messages as $type => $message)
            @php($tone = $toneClasses[$type] ?? $toneClasses['info'])

            <div
                x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => show = false, 5000)"
                x-transition
                data-flash-message
                class="pointer-events-auto rounded-md border bg-white px-4 py-3 text-gray-900 shadow-lg {{ $tone['border'] }}"
                role="alert"
            >
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 shrink-0 {{ $tone['icon'] }}">
                        @if ($type === 'success')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M5 13l4 4L19 7" />
                            </svg>
                        @elseif ($type === 'error')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 9v4m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" />
                            </svg>
                        @elseif ($type === 'warning')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13 16h-1v-4h-1m1-4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" />
                            </svg>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-5">{{ $message }}</p>
                    </div>

                    <button
                        type="button"
                        @click="show = false"
                        data-flash-dismiss
                        class="-mr-1 -mt-1 rounded-md p-1.5 text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $tone['focus'] }}"
                        aria-label="Fermer le message"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-flash-dismiss]');

            if (!button) {
                return;
            }

            const message = button.closest('[data-flash-message]');

            if (message) {
                message.remove();
            }
        });
    </script>
@endif
