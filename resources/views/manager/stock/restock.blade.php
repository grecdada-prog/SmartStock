@extends('manager.layouts.app')

@section('title', 'Reapprovisionner le Stock')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Reapprovisionner le stock</h1>
            <a href="{{ route('manager.stock.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Retour</a>
        </div>

        <div class="rounded-lg bg-white shadow">
            <form method="POST" action="{{ route('manager.stock.restock.store') }}" class="space-y-6 p-6">
                @csrf

                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700">Produit *</label>
                    <select name="product_id" id="product_id" required onchange="updateProductInfo()"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('product_id') border-red-300 @enderror">
                        <option value="">Sélectionner un produit</option>
                        @foreach($products as $product)
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

                <div id="product-info" class="hidden rounded-md bg-blue-50 p-4 text-sm text-blue-800">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <p><span class="font-medium">Stock actuel:</span> <span id="current-stock">-</span></p>
                        <p><span class="font-medium">Seuil d'alerte:</span> <span id="alert-quantity">-</span></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantite *</label>
                        <input type="number" name="quantity" id="quantity" required value="{{ old('quantity') }}" min="1" step="1" oninput="calculateNewStock()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('quantity') border-red-300 @enderror">
                        @error('quantity')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="purchase_price" class="block text-sm font-medium text-gray-700">Prix d'achat unitaire *</label>
                        <input type="number" name="purchase_price" id="purchase_price" required value="{{ old('purchase_price') }}" min="0" step="0.01" oninput="suggestSellingPrice()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('purchase_price') border-red-300 @enderror">
                        @error('purchase_price')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="selling_price" class="block text-sm font-medium text-gray-700">Prix de vente unitaire *</label>
                        <input type="number" name="selling_price" id="selling_price" required value="{{ old('selling_price') }}" min="0" step="0.01" oninput="markSellingPriceEdited()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('selling_price') border-red-300 @enderror">
                        @error('selling_price')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div id="new-stock-display" class="hidden rounded-md bg-rose-50 p-4 text-sm text-rose-800">
                    <span class="font-medium">Nouveau stock:</span> <span id="new-stock">-</span>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                    <a href="{{ route('manager.stock.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</a>
                    <button type="submit" class="rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Reapprovisionner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let sellingPriceEdited = false;

function updateProductInfo() {
    const select = document.getElementById('product_id');
    const selectedOption = select.options[select.selectedIndex];
    const productInfo = document.getElementById('product-info');

    if (!selectedOption.value) {
        productInfo.classList.add('hidden');
        document.getElementById('new-stock-display').classList.add('hidden');
        return;
    }

    const currentStock = selectedOption.getAttribute('data-current-stock');
    const unit = selectedOption.getAttribute('data-unit');
    const alertQty = selectedOption.getAttribute('data-alert');

    document.getElementById('current-stock').textContent = currentStock + ' ' + unit;
    document.getElementById('alert-quantity').textContent = alertQty + ' ' + unit;
    if (!document.getElementById('purchase_price').value) {
        document.getElementById('purchase_price').value = selectedOption.getAttribute('data-purchase-price') || 0;
    }
    if (!document.getElementById('selling_price').value) {
        document.getElementById('selling_price').value = selectedOption.getAttribute('data-selling-price') || 0;
        sellingPriceEdited = false;
    }
    productInfo.classList.remove('hidden');
    calculateNewStock();
}

function markSellingPriceEdited() {
    sellingPriceEdited = true;
}

function suggestSellingPrice() {
    const purchaseInput = document.getElementById('purchase_price');
    const sellingInput = document.getElementById('selling_price');

    if (!purchaseInput || !sellingInput || sellingPriceEdited) {
        return;
    }

    const purchasePrice = Number(purchaseInput.value || 0);

    if (purchasePrice <= 0) {
        sellingInput.value = '';
        return;
    }

    sellingInput.value = (purchasePrice * 1.25).toFixed(2);
}

function calculateNewStock() {
    const select = document.getElementById('product_id');
    const selectedOption = select.options[select.selectedIndex];
    const quantityInput = document.getElementById('quantity');
    const newStockDisplay = document.getElementById('new-stock-display');

    if (selectedOption.value && quantityInput.value) {
        const currentStock = parseInt(selectedOption.getAttribute('data-current-stock'), 10);
        const quantity = parseInt(quantityInput.value, 10);
        const unit = selectedOption.getAttribute('data-unit');
        document.getElementById('new-stock').textContent = (currentStock + quantity) + ' ' + unit;
        newStockDisplay.classList.remove('hidden');
    } else {
        newStockDisplay.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', updateProductInfo);
</script>
@endsection
