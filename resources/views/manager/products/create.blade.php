@extends('manager.layouts.app')

@section('title', 'Créer un Produit')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Créer un Nouveau Produit
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('manager.products.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    Retour
                </a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('manager.products.store') }}" class="space-y-6 p-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Nom du produit -->
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom du produit *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('name') border-red-300 @enderror">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SKU -->
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700">Code SKU *</label>
                        <input type="text" name="sku" id="sku" required value="{{ old('sku') }}"
                            placeholder="Ex: PROD-001"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('sku') border-red-300 @enderror">
                        <p class="mt-1 text-xs text-gray-500">Code unique d'identification du produit</p>
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
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
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
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prix d'achat -->
                    <div>
                        <label for="purchase_price" class="block text-sm font-medium text-gray-700">Prix d'achat (FCFA) *</label>
                        <input type="number" name="purchase_price" id="purchase_price" required value="{{ old('purchase_price') }}"
                            min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('purchase_price') border-red-300 @enderror">
                        @error('purchase_price')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prix de vente -->
                    <div>
                        <label for="selling_price" class="block text-sm font-medium text-gray-700">Prix de vente (FCFA) *</label>
                        <input type="number" name="selling_price" id="selling_price" required value="{{ old('selling_price') }}"
                            min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('selling_price') border-red-300 @enderror">
                        @error('selling_price')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Quantité initiale -->
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantité initiale *</label>
                        <input type="number" name="quantity" id="quantity" required value="{{ old('quantity', 0) }}"
                            min="0" step="1"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('quantity') border-red-300 @enderror">
                        @error('quantity')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Quantité d'alerte -->
                    <div>
                        <label for="alert_quantity" class="block text-sm font-medium text-gray-700">Seuil d'alerte stock *</label>
                        <input type="number" name="alert_quantity" id="alert_quantity" required value="{{ old('alert_quantity', 10) }}"
                            min="0" step="1"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('alert_quantity') border-red-300 @enderror">
                        <p class="mt-1 text-xs text-gray-500">Vous serez alerté quand le stock atteint ce niveau</p>
                        @error('alert_quantity')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unité -->
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700">Unité *</label>
                        <input type="text" name="unit" id="unit" required value="{{ old('unit', 'pièce') }}"
                            placeholder="Ex: pièce, kg, litre..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('unit') border-red-300 @enderror">
                        @error('unit')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Statut actif -->
                    <div class="sm:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Produit actif (disponible à la vente)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Calcul automatique de marge -->
                <div class="rounded-md bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm text-blue-700">
                                <span class="font-medium">Astuce :</span> Le prix de vente doit être supérieur ou égal au prix d'achat pour garantir une marge bénéficiaire.
                            </p>
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
                        Créer le produit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Calcul automatique de la marge bénéficiaire
document.addEventListener('DOMContentLoaded', function() {
    const purchasePrice = document.getElementById('purchase_price');
    const sellingPrice = document.getElementById('selling_price');

    function calculateMargin() {
        const purchase = parseFloat(purchasePrice.value) || 0;
        const selling = parseFloat(sellingPrice.value) || 0;

        if (purchase > 0 && selling > purchase) {
            const margin = ((selling - purchase) / purchase * 100).toFixed(2);
            console.log('Marge bénéficiaire: ' + margin + '%');
        }
    }

    purchasePrice?.addEventListener('input', calculateMargin);
    sellingPrice?.addEventListener('input', calculateMargin);
});
</script>

@endsection
