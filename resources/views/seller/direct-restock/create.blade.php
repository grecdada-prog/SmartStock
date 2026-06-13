@extends('seller.layouts.app')

@section('title', 'Appro direct')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Appro direct</h1>
            <a href="{{ route('seller.pos.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Retour</a>
        </div>

        <div class="rounded-lg bg-white shadow" x-data="{
            showPayModal: false,
            cashType: 'cash',
            totalCost: 0,
            submitting: false,
            openPayModal() {
                const qty = parseFloat(document.getElementById('quantity')?.value || 0);
                const price = parseFloat(document.getElementById('purchase_price')?.value || 0);
                this.totalCost = qty * price;
                if (this.totalCost <= 0) return;
                this.showPayModal = true;
            },
            confirmRestock() {
                this.submitting = true;
                document.getElementById('pay_cash_type').value = this.cashType;
                document.getElementById('restock-form').submit();
            }
        }">
            <form id="restock-form" method="POST" action="{{ route('seller.direct-restock.store') }}" class="space-y-6 p-6">
                @csrf
                <input type="hidden" name="cash_type" id="pay_cash_type" value="cash">

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
                        <p class="text-xs text-gray-600">Modifiez les montants promotionnels sans quitter l'approvisionnement.</p>
                    </div>
                    <div id="promotions-list" class="space-y-3"></div>
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
                    <a href="{{ route('seller.pos.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</a>
                    <button type="button" @click="openPayModal()" class="rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70">Approvisionner</button>
                </div>
            </form>

            <!-- Modal paiement livreur -->
            <div
                x-show="showPayModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center px-4"
                role="dialog"
                aria-modal="true"
            >
                <div class="fixed inset-0 bg-gray-950/50" aria-hidden="true"></div>
                <div class="relative w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
                    <div class="border-b border-rose-100 bg-rose-50 px-5 py-4">
                        <h3 class="text-base font-extrabold text-gray-900">Paiement au livreur</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Vous devez payer
                            <span class="font-bold text-rose-700" x-text="totalCost.toLocaleString('fr-FR') + ' FCFA'"></span>
                            au livreur.
                        </p>
                    </div>
                    <div class="px-5 py-5">
                        <p class="mb-3 text-sm font-medium text-gray-700">Sélectionnez la caisse à débiter :</p>
                        <div class="flex flex-col gap-3">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-4 transition hover:border-rose-300 hover:bg-rose-50" :class="cashType === 'cash' ? 'border-rose-500 bg-rose-50' : ''">
                                <input type="radio" x-model="cashType" value="cash" class="accent-rose-600">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">Caisse Cash</p>
                                    <p class="text-xs text-gray-500">Le montant sera débité de votre solde espèces</p>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-4 transition hover:border-rose-300 hover:bg-rose-50" :class="cashType === 'mobile_money' ? 'border-rose-500 bg-rose-50' : ''">
                                <input type="radio" x-model="cashType" value="mobile_money" class="accent-rose-600">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">Caisse MOMO / OM</p>
                                    <p class="text-xs text-gray-500">Le montant sera débité de votre solde Mobile Money</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="showPayModal = false"
                            :disabled="submitting"
                            class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                        >
                            Annuler
                        </button>
                        <button
                            type="button"
                            @click="confirmRestock()"
                            :disabled="submitting"
                            class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:cursor-wait disabled:opacity-70"
                        >
                            <svg x-show="submitting" class="-ml-1 mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="submitting ? 'Traitement...' : 'Valider et payer'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="product_results_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <div>
                <h3 class="text-base font-extrabold text-gray-900">Resultats produits</h3>
                <p class="text-sm text-gray-500">Choisissez le produit a approvisionner.</p>
            </div>
            <button type="button" id="product_results_close" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer">&times;</button>
        </div>
        <div id="product_results_list" class="max-h-96 overflow-y-auto divide-y divide-gray-100"></div>
    </div>
</div>

<script>
const restockProducts = [];
const productSearchUrl = @json(route('seller.direct-restock.products'));
let sellingPriceEdited = false;

function normalizeBarcode(value) {
    return String(value || '').replace(/\D+/g, '');
}

function productLabel(product) {
    const barcode = product.barcode || 'sans code-barres';
    return `${product.name} - ${barcode} - ${product.quantity} ${product.unit || ''}`.trim();
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
        container.innerHTML = '<p class="px-5 py-8 text-center text-sm text-gray-500">Aucun produit eligible trouve.</p>';
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
        button.addEventListener('click', () => selectRestockProduct(product));
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
        if (!response.ok) throw new Error('Recherche impossible pour le moment.');
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
    if (!block || !list) return;
    list.innerHTML = '';
    if (!product || !Array.isArray(product.promotions) || product.promotions.length === 0) {
        block.classList.add('hidden');
        return;
    }
    product.promotions.forEach((promotion) => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 gap-2 rounded-md border border-rose-100 bg-white p-3 sm:grid-cols-[1fr,8rem] sm:items-end';
        row.innerHTML = `<div><p class="text-sm font-semibold text-gray-900"></p><p class="text-xs text-gray-500"></p></div><div><label class="block text-xs font-medium text-gray-700"></label><input type="number" min="1" step="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"></div>`;
        row.querySelector('.text-gray-900').textContent = promotion.name;
        row.querySelector('.text-gray-500').textContent = `A partir de ${promotion.min_quantity} | ${promotion.status === 'active' ? 'Active' : 'Suspendue'}`;
        const label = row.querySelector('label');
        const input = row.querySelector('input');
        label.setAttribute('for', `promotion_price_${promotion.id}`);
        label.textContent = 'Prix promo';
        input.name = `promotion_prices[${promotion.id}]`;
        input.id = `promotion_price_${promotion.id}`;
        input.value = promotion.promotion_price || '';
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
    if (!purchaseInput || !sellingInput || sellingPriceEdited) return;
    const purchasePrice = Number(purchaseInput.value || 0);
    sellingInput.value = purchasePrice > 0 ? (purchasePrice * 1.30).toFixed(2) : '';
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
    if (!checkbox || !input) return;
    input.required = !checkbox.checked;
    input.disabled = checkbox.checked;
    wrapper?.classList.toggle('hidden', checkbox.checked);
    if (checkbox.checked) input.value = '';
}

document.addEventListener('DOMContentLoaded', () => {
    const productSearch = document.getElementById('product_search');
    document.getElementById('product_search_button')?.addEventListener('click', searchRestockProducts);
    document.getElementById('product_results_close')?.addEventListener('click', hideProductSuggestions);
    productSearch?.addEventListener('input', clearRestockProduct);
    productSearch?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        searchRestockProducts();
    });
    toggleExpirationDate();
});
</script>
@endsection
