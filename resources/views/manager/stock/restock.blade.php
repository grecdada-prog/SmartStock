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
            <form method="POST" action="{{ route('manager.stock.restock.store') }}" data-disable-on-submit class="space-y-6 p-6">
                @csrf

                <div>
                    <label for="product_search" class="block text-sm font-medium text-gray-700">Produit *</label>
                    <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id') }}">
                    <div class="mt-1 flex gap-2">
                        <input type="text" id="product_search" autocomplete="off" placeholder="Rechercher par nom ou code-barres..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('product_id') border-red-300 @enderror">
                        <button type="button" id="product_search_button" class="inline-flex shrink-0 items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700 disabled:cursor-wait disabled:opacity-70">
                            Rechercher
                        </button>
                    </div>
                    <p id="product_search_error" class="mt-2 hidden text-sm text-red-600"></p>
                    @error('product_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="product-info" class="hidden rounded-md bg-blue-50 p-4 text-sm text-blue-800">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <p><span class="font-medium">Stock actuel:</span> <span id="current-stock">-</span></p>
                        <p><span class="font-medium">Seuil d'alerte:</span> <span id="alert-quantity">-</span></p>
                    </div>
                </div>

                <div id="promotions-block" class="hidden rounded-md border border-rose-100 bg-rose-50 p-4">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Promotions de cet article</h3>
                        <p class="text-xs text-gray-600">Modifiez les montants promotionnels sans quitter le reapprovisionnement.</p>
                    </div>
                    <div id="promotions-list" class="space-y-3"></div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantite *</label>
                        <input type="number" name="quantity" id="quantity" required value="{{ old('quantity', $aiRecommendedQuantity) }}" min="1" step="1" oninput="calculateNewStock()"
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

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[auto,1fr] sm:items-end">
                    <label class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" name="non_perishable" id="non_perishable" value="1" {{ old('non_perishable') ? 'checked' : '' }}
                            onchange="toggleExpirationDate()"
                            class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                        Produit non perissable
                    </label>
                    <div id="expiration_date_wrapper">
                        <label for="expiration_date" class="block text-sm font-medium text-gray-700">Date de peremption *</label>
                        <input type="date" name="expiration_date" id="expiration_date" value="{{ old('expiration_date') }}" min="{{ now()->toDateString() }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('expiration_date') border-red-300 @enderror">
                        @error('expiration_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div id="new-stock-display" class="hidden rounded-md bg-rose-50 p-4 text-sm text-rose-800">
                    <span class="font-medium">Nouveau stock:</span> <span id="new-stock">-</span>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                    <a href="{{ route('manager.stock.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</a>
                    <button type="submit" data-submitting-text="Enregistrement..." class="rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70">Reapprovisionner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="product_results_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <div>
                <h3 class="text-base font-extrabold text-gray-900">Resultats produits</h3>
                <p class="text-sm text-gray-500">Choisissez le produit a reapprovisionner.</p>
            </div>
            <button type="button" id="product_results_close" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer">&times;</button>
        </div>
        <div id="product_results_list" class="max-h-96 overflow-y-auto divide-y divide-gray-100"></div>
    </div>
</div>

<script>
const restockProducts = @json($prefillProduct ? [$prefillProduct] : []);
const productSearchUrl = @json(route('manager.products.search'));
const oldRestockProductId = @json(old('product_id', $prefillProduct['id'] ?? null));
let sellingPriceEdited = false;

function normalizeSearch(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function normalizeBarcode(value) {
    return String(value || '').replace(/\D+/g, '');
}

function productLabel(product) {
    const barcode = product.barcode || 'sans code-barres';
    return `${product.name} - ${barcode} - ${product.quantity} ${product.unit || ''}`.trim();
}

function restockMatches(query) {
    const text = normalizeSearch(query);
    const barcode = normalizeBarcode(query);

    if (text.length < 2 && barcode.length < 3) {
        return [];
    }

    return restockProducts.filter((product) => {
        const searchable = normalizeSearch(`${product.name} ${product.barcode || ''}`);
        const productBarcode = normalizeBarcode(product.barcode);
        const matchesText = text.length >= 2 && searchable.includes(text);
        const matchesBarcode = barcode.length >= 3 && productBarcode.includes(barcode);

        return matchesText || matchesBarcode;
    }).slice(0, 10);
}

function hideProductSuggestions() {
    document.getElementById('product_results_modal')?.classList.add('hidden');
    document.getElementById('product_results_modal')?.classList.remove('flex');
}

function renderProductSuggestions(products) {
    const container = document.getElementById('product_results_list');
    if (!container) return;

    container.innerHTML = '';

    if (!products.length) {
        container.innerHTML = '<p class="px-5 py-8 text-center text-sm text-gray-500">Aucun produit trouve.</p>';
        document.getElementById('product_results_modal')?.classList.remove('hidden');
        document.getElementById('product_results_modal')?.classList.add('flex');
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
            selectRestockProduct(product);
        });
        container.appendChild(button);
    });

    document.getElementById('product_results_modal')?.classList.remove('hidden');
    document.getElementById('product_results_modal')?.classList.add('flex');
}

async function searchRestockProducts() {
    const productSearch = document.getElementById('product_search');
    const button = document.getElementById('product_search_button');
    const error = document.getElementById('product_search_error');
    const query = productSearch?.value?.trim() || '';
    const barcode = normalizeBarcode(query);

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
        const response = await fetch(`${productSearchUrl}?q=${encodeURIComponent(query)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Recherche impossible pour le moment.');
        }

        const data = await response.json();
        renderProductSuggestions(data.products || []);
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

function selectedRestockProduct() {
    const productId = Number(document.getElementById('product_id')?.value || 0);
    return restockProducts.find((product) => Number(product.id) === productId) || null;
}

function clearRestockProduct() {
    document.getElementById('product_id').value = '';
    document.getElementById('product-info').classList.add('hidden');
    document.getElementById('promotions-block').classList.add('hidden');
    document.getElementById('promotions-list').innerHTML = '';
    document.getElementById('new-stock-display').classList.add('hidden');
}

function selectRestockProduct(product) {
    if (!restockProducts.some((existingProduct) => Number(existingProduct.id) === Number(product.id))) {
        restockProducts.push(product);
    }

    document.getElementById('product_id').value = product.id;
    document.getElementById('product_search').value = productLabel(product);
    hideProductSuggestions();
    updateProductInfo();
}

function updateProductInfo() {
    const product = selectedRestockProduct();
    const productInfo = document.getElementById('product-info');

    if (!product) {
        productInfo.classList.add('hidden');
        renderPromotions(null);
        document.getElementById('new-stock-display').classList.add('hidden');
        return;
    }

    document.getElementById('current-stock').textContent = `${product.quantity} ${product.unit || ''}`;
    document.getElementById('alert-quantity').textContent = `${product.alert_quantity} ${product.unit || ''}`;
    if (!document.getElementById('purchase_price').value) {
        document.getElementById('purchase_price').value = product.purchase_price || 0;
    }
    if (!document.getElementById('selling_price').value) {
        document.getElementById('selling_price').value = product.selling_price || 0;
        sellingPriceEdited = false;
    }
    productInfo.classList.remove('hidden');
    renderPromotions(product);
    calculateNewStock();
}

function renderPromotions(product) {
    const block = document.getElementById('promotions-block');
    const list = document.getElementById('promotions-list');

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
        label.setAttribute('for', `promotion_price_${promotion.id}`);
        label.textContent = 'Prix promo';
        const input = document.createElement('input');
        input.type = 'number';
        input.name = `promotion_prices[${promotion.id}]`;
        input.id = `promotion_price_${promotion.id}`;
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

    sellingInput.value = (purchasePrice * 1.30).toFixed(2);
}

function calculateNewStock() {
    const product = selectedRestockProduct();
    const quantityInput = document.getElementById('quantity');
    const newStockDisplay = document.getElementById('new-stock-display');

    if (product && quantityInput.value) {
        const quantity = parseInt(quantityInput.value, 10);
        document.getElementById('new-stock').textContent = `${Number(product.quantity) + quantity} ${product.unit || ''}`;
        newStockDisplay.classList.remove('hidden');
    } else {
        newStockDisplay.classList.add('hidden');
    }
}

function toggleExpirationDate() {
    const checkbox = document.getElementById('non_perishable');
    const input = document.getElementById('expiration_date');
    const wrapper = document.getElementById('expiration_date_wrapper');

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
    const productSearch = document.getElementById('product_search');
    const searchButton = document.getElementById('product_search_button');
    const closeButton = document.getElementById('product_results_close');
    const oldProduct = restockProducts.find((product) => String(product.id) === String(oldRestockProductId || ''));

    if (oldProduct) {
        selectRestockProduct(oldProduct);
    }

    productSearch?.addEventListener('input', () => {
        clearRestockProduct();
    });
    searchButton?.addEventListener('click', searchRestockProducts);
    closeButton?.addEventListener('click', hideProductSuggestions);
    productSearch?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        searchRestockProducts();
    });

    updateProductInfo();
    toggleExpirationDate();
});
</script>
@endsection
