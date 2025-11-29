@extends('manager.layouts.app')

@section('title', 'Réapprovisionner le Stock')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Réapprovisionner le Stock
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('manager.stock.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    Retour
                </a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('manager.stock.restock.store') }}" class="space-y-6 p-6">
                @csrf

                <!-- Sélectionner le produit -->
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700">Produit *</label>
                    <select name="product_id" id="product_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('product_id') border-red-300 @enderror"
                        onchange="updateProductInfo()">
                        <option value="">Sélectionner un produit</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-sku="{{ $product->sku }}"
                                    data-current-stock="{{ $product->quantity }}"
                                    data-unit="{{ $product->unit }}"
                                    data-alert="{{ $product->alert_quantity }}"
                                    {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->sku }}) - Stock actuel: {{ $product->quantity }} {{ $product->unit }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info produit sélectionné -->
                <div id="product-info" class="hidden rounded-md bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-blue-800">Informations du produit</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p><strong>Stock actuel:</strong> <span id="current-stock">-</span></p>
                                <p><strong>Seuil d'alerte:</strong> <span id="alert-quantity">-</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quantité à ajouter -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantité à ajouter *</label>
                    <input type="number" name="quantity" id="quantity" required value="{{ old('quantity') }}"
                        min="1" step="1"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('quantity') border-red-300 @enderror"
                        oninput="calculateNewStock()">
                    @error('quantity')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nouveau stock (calculé) -->
                <div id="new-stock-display" class="hidden">
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Nouveau stock après réapprovisionnement</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p class="text-lg font-semibold"><span id="new-stock">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Référence (bon de commande, etc.) -->
                <div>
                    <label for="reference" class="block text-sm font-medium text-gray-700">Référence (Bon de commande, etc.)</label>
                    <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                        placeholder="Ex: BC-2024-001"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('reference') border-red-300 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Optionnel - Pour traçabilité</p>
                    @error('reference')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Raison -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">Raison / Notes</label>
                    <textarea name="reason" id="reason" rows="3"
                        placeholder="Ex: Achat fournisseur, retour client..."
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('reason') border-red-300 @enderror">{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Optionnel - Aide à la traçabilité</p>
                    @error('reason')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Boutons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('manager.stock.index') }}"
                        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        Annuler
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        Réapprovisionner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateProductInfo() {
    const select = document.getElementById('product_id');
    const selectedOption = select.options[select.selectedIndex];
    const productInfo = document.getElementById('product-info');

    if (selectedOption.value) {
        const currentStock = selectedOption.getAttribute('data-current-stock');
        const unit = selectedOption.getAttribute('data-unit');
        const alertQty = selectedOption.getAttribute('data-alert');

        document.getElementById('current-stock').textContent = currentStock + ' ' + unit;
        document.getElementById('alert-quantity').textContent = alertQty + ' ' + unit;
        productInfo.classList.remove('hidden');

        calculateNewStock();
    } else {
        productInfo.classList.add('hidden');
        document.getElementById('new-stock-display').classList.add('hidden');
    }
}

function calculateNewStock() {
    const select = document.getElementById('product_id');
    const selectedOption = select.options[select.selectedIndex];
    const quantityInput = document.getElementById('quantity');
    const newStockDisplay = document.getElementById('new-stock-display');

    if (selectedOption.value && quantityInput.value) {
        const currentStock = parseInt(selectedOption.getAttribute('data-current-stock'));
        const quantity = parseInt(quantityInput.value);
        const unit = selectedOption.getAttribute('data-unit');
        const newStock = currentStock + quantity;

        document.getElementById('new-stock').textContent = newStock + ' ' + unit;
        newStockDisplay.classList.remove('hidden');
    } else {
        newStockDisplay.classList.add('hidden');
    }
}
</script>

@endsection
