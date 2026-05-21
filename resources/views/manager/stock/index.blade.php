@extends('manager.layouts.app')

@section('title', 'Gestion du Stock')

@section('content')
<div id="manager-stock-page" data-silent-refresh x-data="{ showRestockModal: {{ $errors->any() ? 'true' : 'false' }}, showAdjustModal: false }" class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Gestion du Stock</h1>
            <p class="mt-2 text-sm text-gray-700">Vue d'ensemble de votre inventaire</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex flex-wrap items-center gap-3">
            <button type="button" onclick="window.location.reload()" class="inline-flex items-center justify-center rounded-md border border-rose-600 bg-white px-4 py-2 text-sm font-medium text-rose-700 shadow-sm hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Actualiser
            </button>
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

            <button type="button" @click="showRestockModal = true" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Réapprovisionner
            </button>
            <button type="button" @click="showAdjustModal = true" class="inline-flex items-center justify-center rounded-md border border-gray-700 bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Ajustement
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
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
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
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
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
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

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Valeur Stock</dt>
                            <dd>
                                <x-money-toggle
                                    :amount="number_format($stats['total_value'], 0, ',', ' ') . ' FCFA'"
                                    label="la valeur du stock"
                                    value-class="text-xl font-semibold text-gray-900" />
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="sticky top-20 z-20 mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('manager.stock.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom, SKU..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
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

    <!-- Table -->
    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
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
                                                <div class="text-xs text-gray-400">Ajoute le {{ $product->created_at->format('d/m/Y H:i') }}</div>
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

    <!-- Pagination -->
    <div class="mt-6">
        {{ $products->links() }}
    </div>

    <div x-show="showRestockModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6" style="display: none;">
        <div class="fixed inset-0 bg-gray-900/50" @click="showRestockModal = false"></div>
        <div class="relative mx-auto max-w-4xl overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Reapprovisionner le stock</h2>
                    <p class="mt-1 text-sm text-gray-500">Les prix saisis sont des prix unitaires pour chaque article du lot.</p>
                </div>
                <button type="button" @click="showRestockModal = false" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                    <span class="sr-only">Fermer</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('manager.stock.restock.store') }}" class="space-y-6 p-6">
                @csrf

                <div>
                    <label for="modal_restock_product_id" class="block text-sm font-medium text-gray-700">Produit *</label>
                    <select name="product_id" id="modal_restock_product_id" required onchange="updateStockModalProductInfo()"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('product_id') border-red-300 @enderror">
                        <option value="">Sélectionner un produit</option>
                        @foreach($restockProducts as $product)
                            <option value="{{ $product->id }}"
                                data-current-stock="{{ $product->quantity }}"
                                data-unit="{{ $product->unit }}"
                                data-alert="{{ $product->alert_quantity }}"
                                data-purchase-price="{{ $product->purchase_price }}"
                                data-selling-price="{{ $product->selling_price }}"
                                {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->sku }}) - {{ $product->quantity }} {{ $product->unit }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="modal-product-info" class="hidden rounded-md bg-blue-50 p-4 text-sm text-blue-800">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <p><span class="font-medium">Stock actuel:</span> <span id="modal-current-stock">-</span></p>
                        <p><span class="font-medium">Seuil d'alerte:</span> <span id="modal-alert-quantity">-</span></p>
                    </div>
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
                        <input type="number" name="purchase_price" id="modal_purchase_price" required value="{{ old('purchase_price') }}" min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('purchase_price') border-red-300 @enderror">
                        @error('purchase_price')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="modal_selling_price" class="block text-sm font-medium text-gray-700">Prix de vente unitaire *</label>
                        <input type="number" name="selling_price" id="modal_selling_price" required value="{{ old('selling_price') }}" min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('selling_price') border-red-300 @enderror">
                        @error('selling_price')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div id="modal-new-stock-display" class="hidden rounded-md bg-rose-50 p-4 text-sm text-rose-800">
                    <span class="font-medium">Nouveau stock:</span> <span id="modal-new-stock">-</span>
                </div>

                <div>
                    <label for="modal_barcode" class="block text-sm font-medium text-gray-700">Code-barres du nouveau stock *</label>
                    <input type="text" name="barcode" id="modal_barcode" required value="{{ old('barcode') }}" placeholder="6 9455 85 0039 13" pattern="\d \d{4} \d{2} \d{4} \d{2}" inputmode="numeric" maxlength="17" autocomplete="off" data-barcode-format
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('barcode') border-red-300 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Saisissez 13 chiffres, les espaces sont ajoutes automatiquement.</p>
                    @error('barcode')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                    <button type="button" @click="showRestockModal = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</button>
                    <button type="submit" class="rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Reapprovisionner</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showAdjustModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6" style="display: none;">
        <div class="fixed inset-0 bg-gray-900/50" @click="showAdjustModal = false"></div>
        <div class="relative mx-auto max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Ajuster un stock</h2>
                    <p class="mt-1 text-sm text-gray-500">Corrigez la quantite disponible apres verification physique.</p>
                </div>
                <button type="button" @click="showAdjustModal = false" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                    <span class="sr-only">Fermer</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('manager.stock.adjust') }}" class="space-y-6 p-6">
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

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                    <button type="button" @click="showAdjustModal = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</button>
                    <button type="submit" class="rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Enregistrer l'ajustement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
    const select = document.getElementById('modal_restock_product_id');
    const selectedOption = select.options[select.selectedIndex];
    const productInfo = document.getElementById('modal-product-info');

    if (!selectedOption.value) {
        productInfo.classList.add('hidden');
        document.getElementById('modal-new-stock-display').classList.add('hidden');
        return;
    }

    const currentStock = selectedOption.getAttribute('data-current-stock');
    const unit = selectedOption.getAttribute('data-unit');
    const alertQty = selectedOption.getAttribute('data-alert');

    document.getElementById('modal-current-stock').textContent = currentStock + ' ' + unit;
    document.getElementById('modal-alert-quantity').textContent = alertQty + ' ' + unit;

    if (!document.getElementById('modal_purchase_price').value) {
        document.getElementById('modal_purchase_price').value = selectedOption.getAttribute('data-purchase-price') || 0;
    }
    if (!document.getElementById('modal_selling_price').value) {
        document.getElementById('modal_selling_price').value = selectedOption.getAttribute('data-selling-price') || 0;
    }

    productInfo.classList.remove('hidden');
    calculateStockModalNewStock();
}

function calculateStockModalNewStock() {
    const select = document.getElementById('modal_restock_product_id');
    const selectedOption = select.options[select.selectedIndex];
    const quantityInput = document.getElementById('modal_quantity');
    const newStockDisplay = document.getElementById('modal-new-stock-display');

    if (selectedOption.value && quantityInput.value) {
        const currentStock = parseInt(selectedOption.getAttribute('data-current-stock'), 10);
        const quantity = parseInt(quantityInput.value, 10);
        const unit = selectedOption.getAttribute('data-unit');
        document.getElementById('modal-new-stock').textContent = (currentStock + quantity) + ' ' + unit;
        newStockDisplay.classList.remove('hidden');
    } else {
        newStockDisplay.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateStockModalProductInfo();
    updateAdjustModalProductInfo();
});
</script>

@endsection
