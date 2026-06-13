@extends('manager.layouts.app')

@section('title', 'Gestion du Stock')

@section('content')
<div id="manager-stock-page" data-silent-refresh x-data="{ showRestockModal: {{ $errors->any() ? 'true' : 'false' }}, showAdjustModal: false }" class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Gestion du Stock</h1>
            <p class="mt-2 text-sm text-gray-700">Vue d'ensemble de votre inventaire</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex flex-wrap items-center gap-3">
            <a href="{{ route('manager.stock.movements') }}" class="inline-flex items-center justify-center rounded-md border border-purple-600 bg-white px-4 py-2 text-sm font-medium text-purple-600 shadow-sm hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Historique
            </a>
            <a href="{{ route('manager.stock.restocks') }}" class="inline-flex items-center justify-center rounded-md border border-blue-600 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z" />
                </svg>
                Approvisionnements
            </a>
            <a href="{{ route('manager.stock.low-stock') }}" class="inline-flex items-center justify-center rounded-md border border-orange-600 bg-white px-4 py-2 text-sm font-medium text-orange-600 shadow-sm hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Stock faible
            </a>

            <button type="button" @click="showRestockModal = true" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Réapprovisionner
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="smartstore-sticky-cards grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Produits</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_products'] }}</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Stock Faible</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-orange-600">{{ $stats['low_stock_count'] }}</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Rupture de Stock</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-red-600">{{ $stats['out_of_stock_count'] }}</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <x-money-stat-card
            title="Valeur Stock"
            :amount="number_format($stats['total_value'], 0, ',', ' ') . ' FCFA'"
            label="la valeur du stock"
            value-class="text-xl font-semibold text-gray-900"
        />
    </div>

    <!-- Filtres -->
    <div class="bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('manager.stock.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom ou code-barres..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700">Catégorie</label>
                <select name="category_id" id="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Toutes</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="stock_status" class="block text-sm font-medium text-gray-700">Statut Stock</label>
                <select name="stock_status" id="stock_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="in" {{ request('stock_status') == 'in' ? 'selected' : '' }}>En stock</option>
                    <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Stock faible</option>
                    <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Rupture</option>
                </select>
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
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Produit</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix d'achat</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Stock</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix de vente</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Valeur</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($products as $product)
                                <tr class="{{ $product->isOutOfStock() ? 'bg-red-50' : ($product->isLowStock() ? 'bg-orange-50' : '') }}">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                                <div class="text-gray-500">SKU: {{ $product->sku }}</div>
                                                <div class="text-gray-500">Code-barres: {{ $product->barcode ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                        {{ number_format($product->purchase_price, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($product->isOutOfStock())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                0 {{ $product->unit }}
                                            </span>
                                        @elseif($product->isLowStock())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                {{ $product->quantity }} {{ $product->unit }}
                                            </span>
                                        @else
                                            <span class="text-gray-900 font-medium">{{ $product->quantity }} {{ $product->unit }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                        {{ number_format($product->selling_price, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                        {{ number_format($product->quantity * $product->purchase_price, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('manager.stock.products.movements', $product) }}" class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                                Details
                                            </a>
                                            <button type="button"
                                                @click="showAdjustModal = true; $nextTick(() => prepareAdjustModal('{{ $product->id }}'))"
                                                class="inline-flex items-center rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                Ajuster
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucun produit trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(method_exists($products, 'links'))
        <div class="mt-4">
            {{ $products->links() }}
        </div>
    @endif

    <div x-show="showRestockModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-gray-900/50" @click="showRestockModal = false"></div>
        <div class="relative mx-auto w-full max-w-4xl max-h-[calc(100vh-3rem)] rounded-lg bg-white shadow-xl flex flex-col my-6">
            <div class="sticky top-0 z-10 flex flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Reapprovisionner le stock</h2>
                    <p class="mt-1 text-sm text-gray-500">Les prix saisis sont des prix unitaires pour chaque article du lot.</p>
                </div>
                <button type="button" @click="showRestockModal = false" class="flex-shrink-0 rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                    <span class="sr-only">Fermer</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="restock-form" method="POST" action="{{ route('manager.stock.restock.store') }}" data-disable-on-submit class="overflow-y-auto flex-1 space-y-6 p-6">
                @csrf

                <div>
                    <label for="modal_product_search" class="block text-sm font-medium text-gray-700">Produit *</label>
                    <input type="hidden" name="product_id" id="modal_restock_product_id" value="{{ old('product_id') }}">
                    <div class="mt-1 flex gap-2">
                        <input type="text" id="modal_product_search" autocomplete="off" placeholder="Rechercher par nom ou code-barres..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('product_id') border-red-300 @enderror">
                        <button type="button" id="modal_product_search_button" class="inline-flex shrink-0 items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700 disabled:cursor-wait disabled:opacity-70">
                            Rechercher
                        </button>
                    </div>
                    <p id="modal_product_search_error" class="mt-2 hidden text-sm text-red-600"></p>
                    @error('product_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="modal-product-info" class="hidden rounded-md bg-blue-50 p-4 text-sm text-blue-800">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <p><span class="font-medium">Stock actuel:</span> <span id="modal-current-stock">-</span></p>
                        <p><span class="font-medium">Seuil d'alerte:</span> <span id="modal-alert-quantity">-</span></p>
                    </div>
                </div>

                <div id="modal-promotions-block" class="hidden rounded-md border border-rose-100 bg-rose-50 p-4">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Promotions de cet article</h3>
                        <p class="text-xs text-gray-600">Modifiez les montants promotionnels sans quitter le reapprovisionnement.</p>
                    </div>
                    <div id="modal-promotions-list" class="space-y-3"></div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="modal_quantity" class="block text-sm font-medium text-gray-700">Quantite *</label>
                        <input type="number" name="quantity" id="modal_quantity" required value="{{ old('quantity') }}" min="1" step="1" oninput="calculateStockModalNewStock()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('quantity') border-red-300 @enderror">
                        @error('quantity')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="modal_purchase_price" class="block text-sm font-medium text-gray-700">Prix d'achat unitaire *</label>
                        <input type="number" name="purchase_price" id="modal_purchase_price" required value="{{ old('purchase_price') }}" min="0" step="0.01" oninput="suggestModalSellingPrice()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('purchase_price') border-red-300 @enderror">
                        @error('purchase_price')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="modal_selling_price" class="block text-sm font-medium text-gray-700">Prix de vente unitaire *</label>
                        <input type="number" name="selling_price" id="modal_selling_price" required value="{{ old('selling_price') }}" min="0" step="0.01" oninput="markModalSellingPriceEdited()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('selling_price') border-red-300 @enderror">
                        @error('selling_price')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[auto,1fr] sm:items-end">
                    <label class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" name="non_perishable" id="modal_non_perishable" value="1" {{ old('non_perishable') ? 'checked' : '' }}
                            onchange="toggleModalExpirationDate()"
                            class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                        Produit non perissable
                    </label>
                    <div id="modal_expiration_date_wrapper">
                        <label for="modal_expiration_date" class="block text-sm font-medium text-gray-700">Date de peremption *</label>
                        <input type="date" name="expiration_date" id="modal_expiration_date" value="{{ old('expiration_date') }}" min="{{ now()->toDateString() }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('expiration_date') border-red-300 @enderror">
                        @error('expiration_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div id="modal-new-stock-display" class="hidden rounded-md bg-rose-50 p-4 text-sm text-rose-800">
                    <span class="font-medium">Nouveau stock:</span> <span id="modal-new-stock">-</span>
                </div>
            </form>

            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-gray-200 bg-white px-6 py-4 flex-shrink-0">
                <button type="button" @click="showRestockModal = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</button>
                <button type="submit" form="restock-form" data-submitting-text="Enregistrement..." class="rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70">Reapprovisionner</button>
            </div>
        </div>
    </div>

    <div x-show="showAdjustModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-gray-900/50" @click="showAdjustModal = false"></div>
        <div class="relative mx-auto w-full max-w-2xl max-h-[calc(100vh-3rem)] rounded-lg bg-white shadow-xl flex flex-col my-6">
            <div class="sticky top-0 z-10 flex flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Ajuster un stock</h2>
                    <p class="mt-1 text-sm text-gray-500">Corrigez la quantite disponible apres verification physique.</p>
                </div>
                <button type="button" @click="showAdjustModal = false" class="flex-shrink-0 rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                    <span class="sr-only">Fermer</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="adjust-form" method="POST" action="{{ route('manager.stock.adjust') }}" class="overflow-y-auto flex-1 space-y-6 p-6">
                @csrf

                <div>
                    <label for="adjust_product_id" class="block text-sm font-medium text-gray-700">Produit *</label>
                    <select name="product_id" id="adjust_product_id" required onchange="updateAdjustModalProductInfo()"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('product_id') border-red-300 @enderror">
                        <option value="">Selectionner un produit</option>
                        @foreach($restockProducts as $product)
                            <option value="{{ $product->id }}"
                                data-current-stock="{{ $product->quantity }}"
                                data-unit="{{ $product->unit }}"
                                {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->sku }}) - {{ $product->quantity }} {{ $product->unit }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="adjust-product-info" class="hidden rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                    <span class="font-medium">Stock actuel:</span> <span id="adjust-current-stock">-</span>
                </div>

                <div>
                    <label for="adjust_new_quantity" class="block text-sm font-medium text-gray-700">Nouvelle quantite *</label>
                    <input type="number" name="new_quantity" id="adjust_new_quantity" required min="0" step="1" value="{{ old('new_quantity') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('new_quantity') border-red-300 @enderror">
                    @error('new_quantity')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="adjust_reason" class="block text-sm font-medium text-gray-700">Raison *</label>
                    <textarea name="reason" id="adjust_reason" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('reason') border-red-300 @enderror">{{ old('reason') }}</textarea>
                    @error('reason')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </form>

            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-gray-200 bg-white px-6 py-4 flex-shrink-0">
                <button type="button" @click="showAdjustModal = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</button>
                <button type="submit" form="adjust-form" class="rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Enregistrer l'ajustement</button>
            </div>
        </div>
    </div>
</div>

<div id="modal_product_results_modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <div>
                <h3 class="text-base font-extrabold text-gray-900">Resultats produits</h3>
                <p class="text-sm text-gray-500">Choisissez le produit a reapprovisionner.</p>
            </div>
            <button type="button" id="modal_product_results_close" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer">&times;</button>
        </div>
        <div id="modal_product_results_list" class="max-h-96 overflow-y-auto divide-y divide-gray-100"></div>
    </div>
</div>

<script>
const stockModalProducts = [];
const modalProductSearchUrl = @json(route('manager.products.search'));
const oldModalProductId = @json(old('product_id'));
let modalSellingPriceEdited = false;

function normalizeModalSearch(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function normalizeModalBarcode(value) {
    return String(value || '').replace(/\D+/g, '');
}

function modalProductLabel(product) {
    const barcode = product.barcode || 'sans code-barres';
    return `${product.name} - ${barcode} - ${product.quantity} ${product.unit || ''}`.trim();
}

function modalProductMatches(query) {
    const text = normalizeModalSearch(query);
    const barcode = normalizeModalBarcode(query);

    if (text.length < 2 && barcode.length < 3) {
        return [];
    }

    return stockModalProducts.filter((product) => {
        const searchable = normalizeModalSearch(`${product.name} ${product.barcode || ''}`);
        const productBarcode = normalizeModalBarcode(product.barcode);
        const matchesText = text.length >= 2 && searchable.includes(text);
        const matchesBarcode = barcode.length >= 3 && productBarcode.includes(barcode);

        return matchesText || matchesBarcode;
    }).slice(0, 10);
}

function hideModalProductSuggestions() {
    document.getElementById('modal_product_results_modal')?.classList.add('hidden');
    document.getElementById('modal_product_results_modal')?.classList.remove('flex');
}

function renderModalProductSuggestions(products) {
    const container = document.getElementById('modal_product_results_list');
    if (!container) return;

    container.innerHTML = '';

    if (!products.length) {
        container.innerHTML = '<p class="px-5 py-8 text-center text-sm text-gray-500">Aucun produit trouve.</p>';
        document.getElementById('modal_product_results_modal')?.classList.remove('hidden');
        document.getElementById('modal_product_results_modal')?.classList.add('flex');
        return;
    }

    products.forEach((product) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'flex w-full items-start justify-between gap-4 px-5 py-3 text-left hover:bg-rose-50 focus:bg-rose-50 focus:outline-none';
        button.innerHTML = `<span class="min-w-0"><span class="block truncate text-sm font-bold text-gray-900"></span><span class="mt-0.5 block text-xs text-gray-500"></span></span><span class="shrink-0 text-sm font-bold text-rose-600"></span>`;
        button.querySelector('.text-gray-900').textContent = product.name;
        button.querySelector('.text-gray-500').textContent = `Code-barres: ${product.barcode || '-'} | Stock: ${product.quantity} ${product.unit || ''}${product.category ? ' | ' + product.category : ''}`;
        button.querySelector('.text-rose-600').textContent = `${Number(product.selling_price || 0).toLocaleString('fr-FR')} FCFA`;
        button.addEventListener('click', () => {
            selectStockModalProduct(product);
        });
        container.appendChild(button);
    });

    document.getElementById('modal_product_results_modal')?.classList.remove('hidden');
    document.getElementById('modal_product_results_modal')?.classList.add('flex');
}

async function searchStockModalProducts() {
    const productSearch = document.getElementById('modal_product_search');
    const button = document.getElementById('modal_product_search_button');
    const error = document.getElementById('modal_product_search_error');
    const query = productSearch?.value?.trim() || '';
    const barcode = normalizeModalBarcode(query);

    if (error) {
        error.textContent = '';
        error.classList.add('hidden');
    }

    if (query.length < 2 && barcode.length < 3) {
        if (error) {
            error.textContent = 'Saisissez au moins 2 caracteres ou 3 chiffres du code-barres.';
            error.classList.remove('hidden');
        }
        return;
    }

    if (button) {
        button.disabled = true;
        button.textContent = 'Recherche...';
    }

    try {
        const response = await fetch(`${modalProductSearchUrl}?q=${encodeURIComponent(query)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Recherche impossible pour le moment.');
        }

        const data = await response.json();
        renderModalProductSuggestions(data.products || []);
    } catch (searchError) {
        if (error) {
            error.textContent = searchError.message || 'Recherche impossible pour le moment.';
            error.classList.remove('hidden');
        }
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = 'Rechercher';
        }
    }
}

function selectedStockModalProduct() {
    const productId = Number(document.getElementById('modal_restock_product_id')?.value || 0);
    return stockModalProducts.find((product) => Number(product.id) === productId) || null;
}

function clearStockModalProduct() {
    document.getElementById('modal_restock_product_id').value = '';
    document.getElementById('modal-product-info').classList.add('hidden');
    document.getElementById('modal-promotions-block').classList.add('hidden');
    document.getElementById('modal-promotions-list').innerHTML = '';
    document.getElementById('modal-new-stock-display').classList.add('hidden');
}

function selectStockModalProduct(product) {
    if (!stockModalProducts.some((existingProduct) => Number(existingProduct.id) === Number(product.id))) {
        stockModalProducts.push(product);
    }

    document.getElementById('modal_restock_product_id').value = product.id;
    document.getElementById('modal_product_search').value = modalProductLabel(product);
    hideModalProductSuggestions();
    updateStockModalProductInfo();
}

function prepareAdjustModal(productId) {
    const select = document.getElementById('adjust_product_id');
    if (!select) return;

    select.value = productId;
    updateAdjustModalProductInfo();
}

function updateAdjustModalProductInfo() {
    const select = document.getElementById('adjust_product_id');
    const selectedOption = select.options[select.selectedIndex];
    const productInfo = document.getElementById('adjust-product-info');
    const quantityInput = document.getElementById('adjust_new_quantity');

    if (!selectedOption.value) {
        productInfo.classList.add('hidden');
        if (quantityInput) quantityInput.value = '';
        return;
    }

    const currentStock = selectedOption.getAttribute('data-current-stock');
    const unit = selectedOption.getAttribute('data-unit');

    document.getElementById('adjust-current-stock').textContent = currentStock + ' ' + unit;
    if (quantityInput && !quantityInput.value) quantityInput.value = currentStock;
    productInfo.classList.remove('hidden');
}

function updateStockModalProductInfo() {
    const product = selectedStockModalProduct();
    const productInfo = document.getElementById('modal-product-info');

    if (!product) {
        productInfo.classList.add('hidden');
        renderModalPromotions(null);
        document.getElementById('modal-new-stock-display').classList.add('hidden');
        return;
    }

    document.getElementById('modal-current-stock').textContent = `${product.quantity} ${product.unit || ''}`;
    document.getElementById('modal-alert-quantity').textContent = `${product.alert_quantity} ${product.unit || ''}`;

    if (!document.getElementById('modal_purchase_price').value) {
        document.getElementById('modal_purchase_price').value = product.purchase_price || 0;
    }
    if (!document.getElementById('modal_selling_price').value) {
        document.getElementById('modal_selling_price').value = product.selling_price || 0;
        modalSellingPriceEdited = false;
    }

    productInfo.classList.remove('hidden');
    renderModalPromotions(product);
    calculateStockModalNewStock();
}

function renderModalPromotions(product) {
    const block = document.getElementById('modal-promotions-block');
    const list = document.getElementById('modal-promotions-list');

    if (!block || !list) {
        return;
    }

    list.innerHTML = '';

    if (!product || !Array.isArray(product.promotions) || product.promotions.length === 0) {
        block.classList.add('hidden');
        return;
    }

    product.promotions.forEach((promotion) => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 gap-2 rounded-md border border-rose-100 bg-white p-3 sm:grid-cols-[1fr,8rem] sm:items-end';

        const info = document.createElement('div');
        const name = document.createElement('p');
        name.className = 'text-sm font-semibold text-gray-900';
        name.textContent = promotion.name;
        const meta = document.createElement('p');
        meta.className = 'text-xs text-gray-500';
        meta.textContent = `A partir de ${promotion.min_quantity} | ${promotion.status === 'active' ? 'Active' : 'Suspendue'}`;
        info.appendChild(name);
        info.appendChild(meta);

        const field = document.createElement('div');
        const label = document.createElement('label');
        label.className = 'block text-xs font-medium text-gray-700';
        label.setAttribute('for', `modal_promotion_price_${promotion.id}`);
        label.textContent = 'Prix promo';
        const input = document.createElement('input');
        input.type = 'number';
        input.name = `promotion_prices[${promotion.id}]`;
        input.id = `modal_promotion_price_${promotion.id}`;
        input.min = '1';
        input.step = '1';
        input.value = promotion.promotion_price || '';
        input.className = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm';
        field.appendChild(label);
        field.appendChild(input);

        row.appendChild(info);
        row.appendChild(field);
        list.appendChild(row);
    });

    block.classList.remove('hidden');
}

function markModalSellingPriceEdited() {
    modalSellingPriceEdited = true;
}

function suggestModalSellingPrice() {
    const purchaseInput = document.getElementById('modal_purchase_price');
    const sellingInput = document.getElementById('modal_selling_price');

    if (!purchaseInput || !sellingInput || modalSellingPriceEdited) {
        return;
    }

    const purchasePrice = Number(purchaseInput.value || 0);

    if (purchasePrice <= 0) {
        sellingInput.value = '';
        return;
    }

    sellingInput.value = (purchasePrice * 1.30).toFixed(2);
}

function calculateStockModalNewStock() {
    const product = selectedStockModalProduct();
    const quantityInput = document.getElementById('modal_quantity');
    const newStockDisplay = document.getElementById('modal-new-stock-display');

    if (product && quantityInput.value) {
        const quantity = parseInt(quantityInput.value, 10);
        document.getElementById('modal-new-stock').textContent = `${Number(product.quantity) + quantity} ${product.unit || ''}`;
        newStockDisplay.classList.remove('hidden');
    } else {
        newStockDisplay.classList.add('hidden');
    }
}

function toggleModalExpirationDate() {
    const checkbox = document.getElementById('modal_non_perishable');
    const input = document.getElementById('modal_expiration_date');
    const wrapper = document.getElementById('modal_expiration_date_wrapper');

    if (!checkbox || !input) {
        return;
    }

    input.required = !checkbox.checked;
    input.disabled = checkbox.checked;
    if (wrapper) {
        wrapper.classList.toggle('hidden', checkbox.checked);
    }

    if (checkbox.checked) {
        input.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const productSearch = document.getElementById('modal_product_search');
    const searchButton = document.getElementById('modal_product_search_button');
    const closeButton = document.getElementById('modal_product_results_close');
    const oldProduct = stockModalProducts.find((product) => String(product.id) === String(oldModalProductId || ''));

    if (oldProduct) {
        selectStockModalProduct(oldProduct);
    }

    productSearch?.addEventListener('input', () => {
        clearStockModalProduct();
    });
    searchButton?.addEventListener('click', searchStockModalProducts);
    closeButton?.addEventListener('click', hideModalProductSuggestions);
    productSearch?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        searchStockModalProducts();
    });

    updateStockModalProductInfo();
    updateAdjustModalProductInfo();
    toggleModalExpirationDate();
});
</script>

@endsection
