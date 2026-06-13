@extends('manager.layouts.app')

@section('title', 'Modifier un Produit')

@section('content')
@php($isModal = request()->boolean('modal'))
<div class="{{ request()->boolean('modal') ? 'px-0' : 'px-4 sm:px-6 lg:px-8' }}">
    <div class="{{ $isModal ? 'bg-white' : 'max-w-4xl mx-auto' }}">
        @unless($isModal)
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Modifier le Produit
                </h2>
                <p class="mt-1 text-sm text-gray-500">{{ $product->name }} (SKU: {{ $product->sku }})</p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('manager.products.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-colors duration-200">
                    Retour
                </a>
            </div>
        </div>
        @endunless

        <div class="{{ $isModal ? 'bg-white' : 'bg-white shadow rounded-lg' }}">
            <form method="POST" action="{{ $isModal ? route('manager.products.update', ['product' => $product, 'modal' => 1]) : route('manager.products.update', $product) }}" class="{{ $isModal ? 'space-y-5 p-6' : 'space-y-6 p-6' }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Nom du produit -->
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom du produit *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $product->name) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('name') border-red-300 @enderror">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SKU -->
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">Code SKU *</label>
                        <input type="text" id="sku" readonly value="{{ $product->sku }}"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-700 shadow-sm sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Le SKU ne peut pas etre modifie apres creation.</p>
                        @error('sku')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Catégorie -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Catégorie *</label>
                        <select name="category_id" id="category_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('category_id') border-red-300 @enderror">
                            <option value="">Sélectionner une catégorie</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="barcode" class="block text-sm font-medium text-gray-700">Code-barres</label>
                        <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}"
                            maxlength="13" pattern="\d{0,13}" inputmode="numeric" autocomplete="off" data-barcode-format
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('barcode') border-red-300 @enderror">
                        @error('barcode')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Stock actuel (lecture seule) -->
                    <div>
                        <label for="quantity_display" class="block text-sm font-medium text-gray-700">Stock actuel</label>
                        <input type="text" id="quantity_display" value="{{ $product->quantity }} {{ $product->unit }}" disabled
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Pour modifier le stock, utilisez la gestion de stock</p>
                    </div>

                    <!-- Quantité d'alerte -->
                    <div>
                        <label for="alert_quantity" class="block text-sm font-medium text-gray-700">Seuil d'alerte stock *</label>
                        <input type="number" name="alert_quantity" id="alert_quantity" required value="{{ old('alert_quantity', $product->alert_quantity) }}"
                            min="0" step="1"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('alert_quantity') border-red-300 @enderror">
                        @error('alert_quantity')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unité -->
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700">Unité *</label>
                        <input type="text" name="unit" id="unit" required value="{{ old('unit', $product->unit) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('unit') border-red-300 @enderror">
                        @error('unit')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Statut actif -->
                    <div class="sm:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 text-rose-600 focus:ring-rose-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Produit actif (disponible à la vente)
                            </label>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_direct_restock_eligible" id="is_direct_restock_eligible" value="1" {{ old('is_direct_restock_eligible', $product->is_direct_restock_eligible) ? 'checked' : '' }}
                                class="h-4 w-4 text-rose-600 focus:ring-rose-500 border-gray-300 rounded">
                            <label for="is_direct_restock_eligible" class="ml-2 block text-sm text-gray-900">
                                Éligible à l’appro direct
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Statistiques du produit -->
                <div class="{{ $isModal ? 'hidden' : 'grid' }} grid-cols-1 gap-4 sm:grid-cols-3 mt-6">
                    <div class="bg-rose-50 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="w-full min-w-0 flex-1">
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
                                <div class="w-full min-w-0 flex-1">
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
                                <div class="w-full min-w-0 flex-1">
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
                <div class="{{ $isModal ? 'sticky bottom-0 -mx-6 -mb-6 bg-white px-6 py-4' : 'pt-6' }} flex justify-end space-x-3 border-t border-gray-200">
                    <a href="{{ route('manager.products.index') }}"
                        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-colors duration-200">
                        Annuler
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-colors duration-200">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('manager.products._barcode-conflict-modal')

@endsection
