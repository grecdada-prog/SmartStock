@props(['sale'])

<div id="sale-details-modal-{{ $sale->id }}"
     class="fixed inset-0 z-50 hidden overflow-y-auto"
     data-sale-details-modal="{{ $sale->id }}">
    
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" data-sale-details-close="{{ $sale->id }}"></div>

    <!-- Modal -->
    <div class="flex items-center justify-center min-h-screen px-4">
        <div
             class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-3xl sm:w-full"
             data-sale-details-panel>
            
            <!-- Header -->
            <div class="bg-green-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white">
                        Facture {{ $sale->invoice_number }}
                    </h3>
                    <button type="button" data-sale-details-close="{{ $sale->id }}" class="text-white hover:text-gray-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-4">
                <!-- Info vente -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-500">Vendeur</p>
                        <p class="font-medium">{{ $sale->seller->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="font-medium">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Mode de paiement</p>
                        <p class="font-medium">
                            @if($sale->payment_method === 'cash') Espèces
                            @elseif($sale->payment_method === 'card') Orange Money
                            @elseif($sale->payment_method === 'mobile_money') MTN Momo
                            @else {{ $sale->payment_method }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="text-lg font-bold text-green-600">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</p>
                    </div>
                    @if($sale->payment_method === 'cash')
                        <div>
                            <p class="text-sm text-gray-500">Montant recu</p>
                            <p class="font-medium">{{ number_format($sale->amount_received ?? $sale->total, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Monnaie rendue</p>
                            <p class="font-medium">{{ number_format($sale->change_given ?? 0, 0, ',', ' ') }} FCFA</p>
                        </div>
                    @endif
                </div>

                <!-- Articles -->
                <div class="mb-6">
                    <h4 class="font-medium mb-3">Articles vendus</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Prix unitaire</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qté</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sous-total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($sale->items as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-sm">{{ $item->product->name }}</td>
                                        <td class="px-4 py-3 text-sm text-right">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                                        <td class="px-4 py-3 text-sm text-right">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totaux -->
                <div class="border-t pt-4">
                    <div class="flex justify-end space-y-2">
                        <div class="w-64">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Sous-total :</span>
                                <span class="font-medium">{{ number_format($sale->subtotal ?? $sale->total, 0, ',', ' ') }} FCFA</span>
                            </div>
                            @if(isset($sale->tax) && $sale->tax > 0)
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-600">Taxe :</span>
                                    <span class="font-medium">{{ number_format($sale->tax, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif
                            @if(isset($sale->discount) && $sale->discount > 0)
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-600">Remise :</span>
                                    <span class="font-medium text-red-600">-{{ number_format($sale->discount, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif
                            <div class="flex justify-between border-t pt-2">
                                <span class="font-bold">Total :</span>
                                <span class="font-bold text-lg text-green-600">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if(isset($sale->notes) && $sale->notes)
                    <div class="mt-4 p-3 bg-gray-50 rounded">
                        <p class="text-sm text-gray-600"><strong>Notes :</strong> {{ $sale->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                    Imprimer
                </button>
                <button type="button" data-sale-details-close="{{ $sale->id }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const saleId = {{ $sale->id }};
        const modal = document.getElementById(`sale-details-modal-${saleId}`);

        if (!modal || modal.dataset.ready === 'true') {
            return;
        }

        modal.dataset.ready = 'true';

        const open = () => {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };
        const close = () => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        window.addEventListener('open-sale-modal', (event) => {
            if (Number(event.detail?.id) === saleId) {
                open();
            }
        });

        modal.querySelectorAll(`[data-sale-details-close="${saleId}"]`).forEach((button) => {
            button.addEventListener('click', close);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                close();
            }
        });
    });
</script>
