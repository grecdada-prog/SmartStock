@extends('manager.layouts.app')

@section('title', 'Ventes')

@section('content')
<div id="manager-sales-page"
    x-data="{
        deleteSale: { open: false, action: '', invoice: '', seller: '', total: '' },
        paymentStatus: { open: false, loading: false, title: '', status: '', message: '', reference: '', operator: '', total: '', error: '' },
        openDeleteSaleModal(sale) {
            this.deleteSale = { open: true, ...sale };
        },
        closeDeleteSaleModal() {
            this.deleteSale.open = false;
        },
        async verifyPayment(url, sale) {
            this.paymentStatus = { open: true, loading: true, title: sale.invoice, status: '', message: '', reference: '', operator: '', total: '', error: '' };
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                });
                const data = await response.json();
                this.paymentStatus = {
                    open: true,
                    loading: false,
                    title: sale.invoice,
                    status: data.transaction?.status || data.status || '-',
                    message: data.message || '-',
                    reference: data.transaction?.reference || sale.reference || '-',
                    operator: data.transaction?.operator_label || sale.operator || '-',
                    total: data.transaction?.total_amount ? new Intl.NumberFormat('fr-FR').format(data.transaction.total_amount) + ' FCFA' : sale.total,
                    error: response.ok ? '' : (data.message || 'Verification impossible.'),
                };
            } catch (error) {
                this.paymentStatus = { open: true, loading: false, title: sale.invoice, status: '', message: '', reference: '', operator: '', total: '', error: 'Verification impossible pour le moment.' };
            }
        }
    }"
    data-silent-refresh class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Ventes</h1>
            <p class="mt-2 text-sm text-gray-700">Toutes les ventes réalisées par vos vendeurs</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
            <!-- Export Buttons -->
            <x-export-buttons
                :excelRoute="route('manager.sales.export.excel', request()->query())"
                :pdfRoute="route('manager.sales.export.pdf', request()->query())"
            />
        </div>
    </div>

    <!-- Stats Section with Summary -->
    <div class="smartstore-sticky-cards grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Ventes filtrees</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $stats['filtered_sales'] }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Montant total</p>
                    <p class="mt-1 text-lg font-semibold text-rose-600">{{ number_format($stats['filtered_revenue'], 0, ',', ' ') }} FCFA</p>
                </div>
            </div>
        </div>

        <x-money-stat-card
            title="Recette du jour"
            :amount="number_format($stats['total_current_day_revenue'], 0, ',', ' ') . ' FCFA'"
            label="la recette du jour"
            :footer="number_format($stats['today_sales']) . ' vente(s)'"
            value-class="text-lg font-semibold text-gray-700"
        />

        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow">
            <p class="text-sm font-medium text-gray-500">Panier moyen</p>
            <p class="mt-2 text-lg font-semibold text-gray-700">{{ number_format($stats['average_sale'], 0, ',', ' ') }} FCFA</p>
            <p class="mt-2 text-sm text-gray-500">
                {{ number_format($stats['filtered_revenue'], 0, ',', ' ') }} FCFA / {{ $stats['filtered_sales'] }} vente(s)
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow">
            @php($topProductsParams = request()->filled('date_from') || request()->filled('date_to') ? [
                'period' => 'custom',
                'date_from' => request('date_from', request('date_to')),
                'date_to' => request('date_to', request('date_from')),
            ] : [])
            <a href="{{ route('manager.sales.top-products', $topProductsParams) }}" class="text-sm font-medium text-rose-600 hover:text-rose-700">
                Top produits
            </a>
            <div class="mt-2 space-y-1">
                @forelse($stats['top_products'] as $product)
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate font-medium text-gray-700">{{ $loop->iteration }}. {{ $product->name }}</span>
                        <span class="shrink-0 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">{{ (int) $product->total_quantity }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucun produit vendu</p>
                @endforelse
            </div>
        </div>
    </div>
    <!-- Filtres -->
    <div class="bg-white shadow rounded-lg p-4 border border-gray-200">
        <form method="GET" data-auto-filter action="{{ route('manager.sales') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">N° Factures</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="INV-..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                </div>
                <div
                    class="relative"
                    x-data="{
                        productSearch: @js(request('product_search', '')),
                        productId: @js(request('product_id', '')),
                        showProductSuggestions: false,
                        productSuggestions: @js($productSuggestions->map(fn ($product) => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'barcode' => $product->barcode,
                        ])->values()),
                        normalized(value) {
                            return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
                        },
                        normalizedBarcode(value) {
                            return String(value || '').replace(/\D/g, '');
                        },
                        filteredProductSuggestions() {
                            const query = this.normalized(this.productSearch);
                            const barcodeQuery = this.normalizedBarcode(this.productSearch);

                            if (!query && !barcodeQuery) {
                                return [];
                            }

                            return this.productSuggestions.filter((product) => {
                                return this.normalized(product.name).includes(query)
                                    || this.normalized(product.barcode).includes(query)
                                    || (barcodeQuery && this.normalizedBarcode(product.barcode).includes(barcodeQuery));
                            }).slice(0, 8);
                        },
                        chooseProduct(product) {
                            this.productSearch = product.name;
                            this.productId = product.id;
                            this.showProductSuggestions = false;
                            this.$nextTick(() => this.submitProductFilter());
                        },
                        submitProductFilter() {
                            const form = this.$refs.productSearchInput.form;

                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        }
                    }"
                    @click.away="showProductSuggestions = false"
                >
                    <label for="product_search" class="block text-sm font-medium text-gray-700">Produit</label>
                    <input type="hidden" name="product_id" id="product_id" x-model="productId">
                    <input
                        type="text"
                        name="product_search"
                        id="product_search"
                        x-ref="productSearchInput"
                        x-model="productSearch"
                        @focus="showProductSuggestions = true"
                        @input="productId = ''; showProductSuggestions = true"
                        @keydown.enter.prevent.stop="submitProductFilter()"
                        @keydown.escape="showProductSuggestions = false"
                        placeholder="Nom ou code-barres..."
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                    >
                    <div
                        x-show="showProductSuggestions && filteredProductSuggestions().length > 0"
                        x-cloak
                        class="absolute z-40 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
                    >
                        <template x-for="product in filteredProductSuggestions()" :key="product.id">
                            <button
                                type="button"
                                @mousedown.prevent="chooseProduct(product)"
                                class="flex w-full flex-col px-3 py-2 text-left text-sm hover:bg-rose-50 focus:bg-rose-50 focus:outline-none"
                            >
                                <span class="font-semibold text-gray-900" x-text="product.name"></span>
                                <span class="text-xs text-gray-500" x-text="product.barcode ? 'Code-barres: ' + product.barcode : 'Code-barres: -'"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">Mode de paiement</label>
                    <select name="payment_method" id="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        <option value="">Tous</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Espèces</option>
                        <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Orange Money</option>
                        <option value="mobile_money" {{ request('payment_method') == 'mobile_money' ? 'selected' : '' }}>MTN Momo</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Date début</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Date fin</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                </div>
            </div>
            <div class="flex gap-2 flex-wrap">
                <button type="button" onclick="document.getElementById('search').value=''; document.getElementById('product_search').value=''; document.getElementById('product_id').value=''; document.getElementById('payment_method').value=''; document.getElementById('date_from').value=''; document.getElementById('date_to').value=''; this.closest('form').submit();" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 border border-gray-300">Tout</button>
                <button type="button" onclick="const today = new Date().toISOString().split('T')[0]; document.getElementById('date_from').value = today; document.getElementById('date_to').value = today; this.closest('form').submit();" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 border border-gray-300">Aujourd'hui</button>
                <button type="button" onclick="const today = new Date(); const startOfWeek = new Date(today); startOfWeek.setDate(today.getDate() - today.getDay()); document.getElementById('date_from').value = startOfWeek.toISOString().split('T')[0]; document.getElementById('date_to').value = today.toISOString().split('T')[0]; this.closest('form').submit();" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 border border-gray-300">Cette semaine</button>
                <button type="button" onclick="const today = new Date(); const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1); document.getElementById('date_from').value = startOfMonth.toISOString().split('T')[0]; document.getElementById('date_to').value = today.toISOString().split('T')[0]; this.closest('form').submit();" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 border border-gray-300">Ce mois</button>
            </div>
        </form>
    </div>
        </div>
    </div>

    <!-- Table -->
    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-visible sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-visible shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Nº FACTURE</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">DATE-HEURE</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">VENDEUR</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">ARTICLES</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">PAIEMENT</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">TOTAL</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($sales as $sale)
                                @php($paymentTransaction = $sale->paymentTransactions->last())
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-mono font-medium text-gray-900 sm:pl-6">
                                        {{ $sale->invoice_number }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div>{{ $sale->created_at->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $sale->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                        {{ $sale->seller->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $sale->items->count() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($sale->payment_method === 'cash')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                                Espèces
                                            </span>
                                        @elseif($sale->payment_method === 'card')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Orange Money
                                            </span>
                                        @elseif($sale->payment_method === 'mobile_money')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                MTN Momo
                                            </span>
                                        @else
                                            <span class="text-gray-500">{{ $sale->payment_method }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-rose-600">
                                        {{ number_format($sale->total, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <button type="button"
                                                    onclick="window.dispatchEvent(new CustomEvent('open-sale-modal', { detail: { id: {{ $sale->id }} } }))"
                                                    class="px-3 py-1 rounded-md border border-blue-200 text-blue-600 hover:bg-blue-50 transition">
                                                Détails
                                            </button>
                                            @if($paymentTransaction && in_array($sale->payment_method, ['card', 'mobile_money'], true))
                                                <button type="button"
                                                        @click="verifyPayment(@js(route('manager.payment-transactions.check', $paymentTransaction)), {
                                                            invoice: @js($sale->invoice_number),
                                                            reference: @js($paymentTransaction->reference),
                                                            operator: @js($paymentTransaction->operator_label),
                                                            total: @js(number_format($paymentTransaction->total_amount, 0, ',', ' ').' FCFA')
                                                        })"
                                                        class="rounded-md border border-amber-200 px-3 py-1 text-amber-700 transition hover:bg-amber-50">
                                                    Vérifier
                                                </button>
                                            @endif
                                            <button type="button"
                                                    @click="openDeleteSaleModal({
                                                        action: @js(route('manager.sales.destroy', $sale)),
                                                        invoice: @js($sale->invoice_number),
                                                        seller: @js($sale->seller->name ?? 'N/A'),
                                                        total: @js(number_format($sale->total, 0, ',', ' ').' FCFA')
                                                    })"
                                                    class="inline-flex h-12 w-12 items-center justify-center rounded-md bg-transparent text-red-600 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                                    title="Supprimer">
                                                <span class="sr-only">Supprimer</span>
                                                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18" />
                                                    <path d="M8 6V4h8v2" />
                                                    <path d="M6 6l1 15h10l1-15" />
                                                    <path d="M10 11v6" />
                                                    <path d="M14 11v6" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucune vente trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @if(method_exists($sales, 'links'))
        <div class="mt-4">
            {{ $sales->links() }}
        </div>
    @endif
    <div x-show="deleteSale.open"
         x-cloak
         class="fixed inset-0 z-50"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div class="flex min-h-screen items-center justify-center px-4 py-6 text-center">
            <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="closeDeleteSaleModal()" aria-hidden="true"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-lg bg-white text-left shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Supprimer la vente</h3>
                    <button type="button" @click="closeDeleteSaleModal()" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <div class="flex gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v2H9V5a1 1 0 011-1z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Cette action supprimera definitivement la vente et corrigera automatiquement le stock ainsi que le solde du vendeur.</p>
                            <dl class="mt-4 grid grid-cols-1 gap-2 rounded-md bg-gray-50 p-4 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">Facture</dt>
                                    <dd class="font-semibold text-gray-900" x-text="deleteSale.invoice"></dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">Vendeur</dt>
                                    <dd class="font-semibold text-gray-900" x-text="deleteSale.seller"></dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">Montant</dt>
                                    <dd class="font-semibold text-rose-600" x-text="deleteSale.total"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-6 py-4 sm:flex sm:flex-row-reverse">
                    <form method="POST" :action="deleteSale.action">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto">
                            Supprimer
                        </button>
                    </form>
                    <button type="button" @click="closeDeleteSaleModal()" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:mt-0 sm:w-auto">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div x-show="paymentStatus.open"
         x-cloak
         class="fixed inset-0 z-50"
         role="dialog"
         aria-modal="true"
         style="display: none;">
        <div class="flex min-h-screen items-center justify-center px-4 py-6 text-center">
            <div class="fixed inset-0 bg-gray-900/50" @click="paymentStatus.open = false" aria-hidden="true"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-lg bg-white text-left shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-rose-600">Paiement mobile</p>
                        <h3 class="text-lg font-semibold text-gray-900" x-text="paymentStatus.title"></h3>
                    </div>
                    <button type="button" @click="paymentStatus.open = false" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5">
                    <p x-show="paymentStatus.loading" class="text-sm font-semibold text-gray-700">Verification en cours...</p>
                    <p x-show="paymentStatus.error" class="rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-700" x-text="paymentStatus.error"></p>
                    <dl x-show="!paymentStatus.loading" class="space-y-3 rounded-md bg-rose-50 px-4 py-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-gray-600">Reference</dt><dd class="font-bold text-gray-900" x-text="paymentStatus.reference"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-600">Operateur</dt><dd class="font-bold text-gray-900" x-text="paymentStatus.operator"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-600">Montant</dt><dd class="font-bold text-rose-600" x-text="paymentStatus.total"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-600">Statut</dt><dd class="font-bold text-gray-900" x-text="paymentStatus.status"></dd></div>
                    </dl>
                    <p x-show="!paymentStatus.loading && paymentStatus.message" class="mt-3 text-sm font-semibold text-gray-700" x-text="paymentStatus.message"></p>
                </div>
                <div class="border-t border-gray-200 px-6 py-4 text-right">
                    <button type="button" @click="paymentStatus.open = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals -->
    @foreach($sales as $sale)
        <x-sale-details-modal :sale="$sale" />
    @endforeach
</div>

@endsection
