@props(['sale'])

<div id="sale-details-modal-{{ $sale->id }}"
     class="fixed inset-0 z-50 hidden px-4 py-6"
     data-sale-details-modal="{{ $sale->id }}">

    <div class="fixed inset-0 bg-slate-950/55 transition-opacity" data-sale-details-close="{{ $sale->id }}"></div>

    <div class="relative flex min-h-full items-center justify-center">
        <div class="w-full max-w-5xl rounded-xl bg-slate-50 p-4 shadow-2xl sm:p-6" data-sale-details-panel>
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-normal text-gray-500">Facture</p>
                    <h3 class="text-lg font-extrabold text-gray-950">{{ $sale->invoice_number }}</h3>
                </div>
                <button type="button"
                        data-sale-details-close="{{ $sale->id }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-xl leading-none text-gray-700 transition hover:bg-gray-50"
                        aria-label="Fermer">
                    &times;
                </button>
            </div>

            <x-sale-receipt-card :sale="$sale" :show-actions="true" />
        </div>
    </div>
</div>
