@extends('manager.layouts.app')

@section('title', 'Gestion des Produits')

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="{ autoRefresh: true }" x-init="
    setInterval(() => {
        if (autoRefresh) {
            window.location.reload();
        }
    }, 5000);
">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Mes Produits</h1>
            <p class="mt-2 text-sm text-gray-700">Liste de tous vos produits en stock</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
            <!-- Toggle Auto-refresh -->
            <label class="flex items-center space-x-2 text-sm text-gray-700">
                <input type="checkbox" x-model="autoRefresh" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span>Auto-refresh 5s</span>
            </label>

            <a href="{{ route('manager.products.low-stock') }}" class="inline-flex items-center justify-center rounded-md border border-orange-600 bg-white px-4 py-2 text-sm font-medium text-orange-600 shadow-sm hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Stock Faible
            </a>

            <a href="{{ route('manager.products.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau Produit
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('manager.products.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom, SKU..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
            </div>
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700">Catégorie</label>
                <select name="category_id" id="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                    <option value="">Toutes</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>
            <div>
                <label for="stock_status" class="block text-sm font-medium text-gray-700">Stock</label>
                <select name="stock_status" id="stock_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="in" {{ request('stock_status') == 'in' ? 'selected' : '' }}>En stock</option>
                    <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Stock faible</option>
                    <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Rupture</option>
                </select>
            </div>
            <div class="sm:col-span-4 flex justify-end">
                <button type="submit" class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    Filtrer
                </button>
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
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Catégorie</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix Vente</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Stock</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Statut</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($products as $product)
                                <tr class="{{ $product->isOutOfStock() ? 'bg-red-50' : ($product->isLowStock() ? 'bg-yellow-50' : '') }}">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                                <div class="text-gray-500">SKU: {{ $product->sku }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $product->category->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-medium">
                                        {{ number_format($product->selling_price, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <div class="flex items-center">
                                            @if($product->isOutOfStock())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                                        <circle cx="4" cy="4" r="3" />
                                                    </svg>
                                                    Rupture
                                                </span>
                                            @elseif($product->isLowStock())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-orange-400" fill="currentColor" viewBox="0 0 8 8">
                                                        <circle cx="4" cy="4" r="3" />
                                                    </svg>
                                                    {{ $product->quantity }} {{ $product->unit }}
                                                </span>
                                            @else
                                                <span class="text-gray-900">{{ $product->quantity }} {{ $product->unit }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($product->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Actif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-end space-x-3">
                                            <!-- Bouton Voir -->
                                            <a href="{{ route('manager.products.show', $product) }}" class="text-indigo-600 hover:text-indigo-900" title="Voir détails">
                                                Voir
                                            </a>

                                            <!-- Bouton Modifier -->
                                            <a href="{{ route('manager.products.edit', $product) }}" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                                Modifier
                                            </a>

                                            <!-- Bouton Activer/Désactiver -->
                                            <form method="POST" action="{{ route('manager.products.toggle-status', $product) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-orange-600 hover:text-orange-900" title="{{ $product->is_active ? 'Désactiver' : 'Activer' }}">
                                                    {{ $product->is_active ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>

                                            <!-- Bouton Supprimer -->
                                            <div x-data="{ deleteProductId: null }">
                                                <form method="POST"
                                                      action="{{ route('manager.products.destroy', $product) }}"
                                                      x-on:modal-confirmed-delete-product.window="if(deleteProductId === {{ $product->id }}) $el.submit()">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            @click="deleteProductId = {{ $product->id }}; $dispatch('open-modal-delete-product')"
                                                            class="text-red-600 hover:text-red-900"
                                                            title="Supprimer">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucun produit trouvé.
                                        <a href="{{ route('manager.products.create') }}" class="text-green-600 hover:text-green-900 font-medium">Créer votre premier produit</a>
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
</div>

<!-- Modal de confirmation de suppression -->
<x-modal-confirm
    id="delete-product"
    title="Supprimer le produit"
    message="Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible."
    confirmText="Oui, supprimer"
    cancelText="Annuler"
    type="danger" />

@endsection
