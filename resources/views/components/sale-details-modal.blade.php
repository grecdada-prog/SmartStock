@props(['sale'])

<div id="sale-details-modal-{{ $sale->id }}"
     class="fixed inset-0 z-50 hidden px-4 py-6 flex items-center justify-center"
     data-sale-details-modal="{{ $sale->id }}">

    <div class="fixed inset-0 bg-gray-900/50 transition-opacity" data-sale-details-close="{{ $sale->id }}"></div>

    <div class="relative flex w-full max-h-[calc(100vh-3rem)] max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-xl" data-sale-details-panel>
        <div class="shrink-0 flex items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 py-4">
            <div>
                <h3 class="text-lg font-extrabold text-gray-950">{{ $sale->invoice_number }}</h3>
            </div>
            <button type="button"
                    data-sale-details-close="{{ $sale->id }}"
                    class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-md bg-white text-xl leading-none text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                    aria-label="Fermer">
                &times;
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <x-sale-receipt-card :sale="$sale" :show-actions="true" />
        </div>
    </div>
</div>
