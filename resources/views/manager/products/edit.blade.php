@extends('manager.layouts.app')

@section('title', 'Modifier un Produit')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Modifier le Produit
                </h2>
                <p class="mt-1 text-sm text-gray-500">{{ $product->name }} (SKU: {{ $product->sku }})</p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('manager.products.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    Retour
                </a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('manager.products.update', $product) }}" class="space-y-6 p-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Nom du produit -->
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom du produit *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $product->name) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('name') border-red-300 @enderror">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SKU -->
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">Code SKU *</label>
                        <input type="text" name="sku" id="sku" required value="{{ old('sku', $product->sku) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('sku') border-red-300 @enderror">
                        @error('sku')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Catégorie -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Catégorie *</label>
                        <select name="category_id" id="category_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('category_id') border-red-300 @enderror">
                            <option value="">Sélectionner une catégorie</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prix d'achat -->
                    <div>
                        <label for="purchase_price" class="block text-sm font-medium text-gray-700">Prix d'achat (FCFA) *</label>
                        <input type="number" name="purchase_price" id="purchase_price" required value="{{ old('purchase_price', $product->purchase_price) }}"
                            min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('purchase_price') border-red-300 @enderror">
                        @error('purchase_price')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prix de vente -->
                    <div>
                        <label for="selling_price" class="block text-sm font-medium text-gray-700">Prix de vente (FCFA) *</label>
                        <input type="number" name="selling_price" id="selling_price" required value="{{ old('selling_price', $product->selling_price) }}"
                            min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('selling_price') border-red-300 @enderror">
                        @error('selling_price')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Stock actuel (lecture seule) -->
                    <div>
                        <label for="quantity_display" class="block text-sm font-medium text-gray-700">Stock actuel</label>
                        <input type="text" id="quantity_display" value="{{ $product->quantity }} {{ $product->unit }}" disabled
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Pour modifier le stock, utilisez la gestion de stock</p>
                    </div>

                    <!-- Quantité d'alerte -->
                    <div>
                        <label for="alert_quantity" class="block text-sm font-medium text-gray-700">Seuil d'alerte stock *</label>
                        <input type="number" name="alert_quantity" id="alert_quantity" required value="{{ old('alert_quantity', $product->alert_quantity) }}"
                            min="0" step="1"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('alert_quantity') border-red-300 @enderror">
                        @error('alert_quantity')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unité -->
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700">Unité *</label>
                        <input type="text" name="unit" id="unit" required value="{{ old('unit', $product->unit) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('unit') border-red-300 @enderror">
                        @error('unit')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Statut actif -->
                    <div class="sm:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Produit actif (disponible à la vente)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Statistiques du produit -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mt-6">
                    <div class="bg-green-50 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Marge</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ number_format($product->profitMargin(), 2) }}%</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Ventes</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ $product->saleItems->count() }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-purple-50 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Créé le</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ $product->created_at->format('d/m/Y') }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('manager.products.index') }}"
                        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        Annuler
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
