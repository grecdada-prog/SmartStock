@extends('superadmin.layouts.app')

@section('title', 'Ventes')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Ventes</h1>
            <p class="mt-2 text-sm text-gray-700">Suivi global des ventes et des encaissements.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16">
            <x-export-buttons
                :excelRoute="route('superadmin.sales.export.excel', request()->query())"
                :pdfRoute="route('superadmin.sales.export.pdf', request()->query())" />
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

        <x-money-stat-card
            title="Caisse Cash"
            :amount="number_format($stats['total_cash_balance'], 0, ',', ' ') . ' FCFA'"
            label="le Caisse Cash"
            value-class="text-lg font-semibold text-gray-700"
        />

        <x-money-stat-card
            title="Caisse MOMO/OM"
            :amount="number_format($stats['total_mobile_money_balance'], 0, ',', ' ') . ' FCFA'"
            label="la Caisse MOMO/OM"
            value-class="text-lg font-semibold text-gray-700"
        />
    </div>
    <!-- Filtres -->
    <div class="bg-white shadow rounded-lg p-4 border border-gray-200">
        <form method="GET" data-auto-filter action="{{ route('superadmin.sales') }}" class="space-y-4">
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
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">Paiement</label>
                    <select name="payment_method" id="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        <option value="">Tous</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Especes</option>
                        <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Orange Money</option>
                        <option value="mobile_money" {{ request('payment_method') == 'mobile_money' ? 'selected' : '' }}>MTN Momo</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Date debut</label>
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
                                                Especes
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
                                        <button type="button"
                                                onclick="window.dispatchEvent(new CustomEvent('open-sale-modal', { detail: { id: {{ $sale->id }} } }))"
                                                class="px-3 py-1 rounded-md border border-blue-200 text-blue-600 hover:bg-blue-50 transition">
                                            Détails
                                        </button>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucune vente trouvee
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        {{ $sales->links() }}
    </div>

    <!-- Modals -->
    @foreach($sales as $sale)
        <x-sale-details-modal :sale="$sale" />
    @endforeach
</div>
@endsection
