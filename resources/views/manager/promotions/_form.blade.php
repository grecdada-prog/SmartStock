@php
    $productPayload = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'unit' => $product->unit,
            'selling_price' => (float) $product->selling_price,
            'quantity' => $product->quantity,
            'promotions' => $product->promotions->map(fn ($existingPromotion) => [
                'id' => $existingPromotion->id,
                'name' => $existingPromotion->name,
                'promotion_price' => (float) $existingPromotion->promotion_price,
                'min_quantity' => $existingPromotion->min_quantity,
                'status' => $existingPromotion->status,
                'delete_url' => route('manager.promotions.destroy', $existingPromotion),
            ])->values(),
            'batches' => $product->stockMovements->map(fn ($batch) => [
                'code' => $batch->batch_code ?? 'LOT-'.$batch->id,
                'remaining_quantity' => $batch->remaining_quantity,
                'selling_price' => (float) $batch->selling_price,
            ])->values(),
        ];
    })->values();
@endphp

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-5"
    x-data="promotionForm({
        products: @js($productPayload),
        searchUrl: @js(route('manager.products.search')),
        selectedProductId: @js((string) old('product_id', $promotion->product_id)),
        promotionPrice: @js(old('promotion_price', $promotion->promotion_price)),
        selectedPromotionId: @js($promotion->id),
    })"
>
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div>
                <label for="product_id" class="block text-sm font-medium text-gray-700">Produit *</label>
                <input type="hidden" name="product_id" x-model="selectedProductId">
                <div class="mt-1 flex gap-2">
                    <input
                        id="product_id"
                        type="text"
                        x-model="productSearch"
                        @input="selectedProductId = ''; searchError = ''"
                        @keydown.enter.prevent="searchProducts()"
                        required
                        autocomplete="off"
                        placeholder="Nom, debut du nom, code-barres..."
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                    >
                    <button type="button" @click="searchProducts()" :disabled="searchingProducts" class="inline-flex shrink-0 items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700 disabled:cursor-wait disabled:opacity-70">
                        <span x-text="searchingProducts ? 'Recherche...' : 'Rechercher'"></span>
                    </button>
                </div>
                <p x-show="searchError" x-cloak class="mt-1 text-sm text-red-600" x-text="searchError"></p>
                @error('product_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nom de la promotion *</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $promotion->name) }}"
                    required
                    maxlength="255"
                    placeholder="Ex: Pack special, remise stock ancien..."
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                >
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="promotion_price" class="block text-sm font-medium text-gray-700">Prix promotionnel unitaire *</label>
                    <input
                        type="number"
                        id="promotion_price"
                        name="promotion_price"
                        x-model.number="promotionPrice"
                        required
                        min="1"
                        step="1"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                    >
                    @error('promotion_price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="min_quantity" class="block text-sm font-medium text-gray-700">Quantite minimum *</label>
                    <input
                        type="number"
                        id="min_quantity"
                        name="min_quantity"
                        value="{{ old('min_quantity', $promotion->min_quantity ?? 1) }}"
                        required
                        min="1"
                        step="1"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                    >
                    @error('min_quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Statut *</label>
                <select
                    id="status"
                    name="status"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                >
                    <option value="active" @selected(old('status', $promotion->status ?? 'active') === 'active')>Active</option>
                    <option value="suspended" @selected(old('status', $promotion->status ?? 'active') === 'suspended')>Suspendue</option>
                </select>
                @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Prix actuels du produit</h3>
            <template x-if="selectedProduct">
                <div class="mt-4 space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedProduct.name"></p>
                        <p class="text-xs text-gray-500">
                            Code-barres: <span x-text="selectedProduct.barcode || '-'"></span> - stock
                            <span x-text="selectedProduct.quantity"></span>
                            <span x-text="selectedProduct.unit"></span>
                        </p>
                    </div>
                    <div class="space-y-2">
                        <template x-for="batch in visibleBatches" :key="batch.code">
                            <div class="rounded-md border border-gray-100 bg-gray-50 px-3 py-2">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-gray-700" x-text="batch.code"></span>
                                    <span class="font-semibold text-gray-900" x-text="formatPrice(batch.selling_price)"></span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Reste <span x-text="batch.remaining_quantity"></span> <span x-text="selectedProduct.unit"></span>
                                </p>
                            </div>
                        </template>
                        <template x-if="visibleBatches.length === 0">
                            <div class="rounded-md border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                                Prix produit: <span class="font-semibold" x-text="formatPrice(selectedProduct.selling_price)"></span>
                            </div>
                        </template>
                    </div>
                    <p x-show="suggestedSaving > 0" class="rounded-md bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
                        Reduction estimee: <span x-text="formatPrice(suggestedSaving)"></span> par article.
                    </p>

                    <div class="border-t border-gray-100 pt-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">Promotions existantes</p>
                        <template x-if="existingPromotions.length === 0">
                            <p class="mt-2 text-sm text-gray-500">Aucune promotion existante pour ce produit.</p>
                        </template>
                        <div class="mt-2 space-y-2">
                            <template x-for="existingPromotion in existingPromotions" :key="existingPromotion.id">
                                <div class="flex items-start justify-between gap-3 rounded-md border border-gray-100 bg-white px-3 py-2 shadow-sm">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-gray-900" x-text="existingPromotion.name"></p>
                                        <p class="text-xs text-gray-500">
                                            <span x-text="formatPrice(existingPromotion.promotion_price)"></span>
                                            - a partir de <span x-text="existingPromotion.min_quantity"></span>
                                            - <span x-text="existingPromotion.status === 'active' ? 'Active' : 'Suspendue'"></span>
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="confirmDeletePromotion(existingPromotion)"
                                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-lg leading-none text-red-600 transition hover:bg-red-50 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1"
                                        title="Supprimer la promotion"
                                        aria-label="Supprimer la promotion"
                                    >
                                        <span aria-hidden="true">&#128465;</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="showSearchResults" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/50 px-4">
        <div class="w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl" @click.away="showSearchResults = false">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Resultats produits</h3>
                    <p class="text-sm text-gray-500">Choisissez le produit a utiliser pour cette promotion.</p>
                </div>
                <button type="button" @click="showSearchResults = false" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer">&times;</button>
            </div>
            <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                <template x-if="searchResults.length === 0">
                    <p class="px-5 py-8 text-center text-sm text-gray-500">Aucun produit trouve.</p>
                </template>
                <template x-for="product in searchResults" :key="product.id">
                    <button type="button" @click="chooseProduct(product)" class="flex w-full items-start justify-between gap-4 px-5 py-3 text-left hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-gray-900" x-text="product.name"></span>
                            <span class="block text-xs text-gray-500">
                                Code-barres: <span x-text="product.barcode || '-'"></span> - Stock
                                <span x-text="product.quantity"></span>
                                <span x-text="product.unit"></span>
                                <span x-show="product.category"> - <span x-text="product.category"></span></span>
                            </span>
                        </span>
                        <span class="shrink-0 text-sm font-bold text-rose-600" x-text="formatPrice(product.selling_price)"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('manager.promotions.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</a>
        <button type="submit" class="rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
            {{ $submitLabel }}
        </button>
    </div>

    <div
        x-show="pendingDeletePromotion"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/60 px-4"
    >
        <div
            x-show="pendingDeletePromotion"
            x-transition.scale.origin.center
            @click.away="!deletingPromotion && cancelDeletePromotion()"
            class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-2xl"
        >
            <div class="bg-rose-600 px-6 py-5 text-white">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-extrabold">Supprimer la promotion</h3>
                        <p class="mt-0.5 text-sm text-white/80">SmartStore va retirer uniquement cette promotion.</p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5">
                <p class="text-sm text-gray-700">
                    Confirmer la suppression de
                    <span class="font-extrabold text-gray-950" x-text="pendingDeletePromotion?.name"></span>
                    ?
                </p>
                <p x-show="deletePromotionError" class="mt-3 rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-700" x-text="deletePromotionError"></p>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                <button
                    type="button"
                    @click="cancelDeletePromotion()"
                    :disabled="deletingPromotion"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50 disabled:opacity-60"
                >
                    Annuler
                </button>
                <button
                    type="button"
                    @click="deleteExistingPromotion()"
                    :disabled="deletingPromotion"
                    class="inline-flex items-center justify-center rounded-md bg-rose-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-rose-700 disabled:cursor-wait disabled:opacity-70"
                >
                    <svg x-show="deletingPromotion" class="-ml-1 mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="deletingPromotion ? 'Suppression...' : 'Supprimer'"></span>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    window.promotionForm = function ({ products, searchUrl, selectedProductId, promotionPrice, selectedPromotionId }) {
        return {
            products,
            searchUrl,
            selectedProductId: String(selectedProductId || ''),
            promotionPrice: Number(promotionPrice || 0),
            selectedPromotionId: selectedPromotionId ? String(selectedPromotionId) : '',
            productSearch: '',
            searchResults: [],
            searchingProducts: false,
            showSearchResults: false,
            searchError: '',
            pendingDeletePromotion: null,
            deletingPromotion: false,
            deletePromotionError: '',
            init() {
                if (this.selectedProduct) {
                    this.productSearch = this.productLabel(this.selectedProduct);
                }
            },
            get selectedProduct() {
                return this.products.find(product => String(product.id) === String(this.selectedProductId)) || null;
            },
            get existingPromotions() {
                if (!this.selectedProduct) {
                    return [];
                }

                return (this.selectedProduct.promotions || [])
                    .filter(promotion => String(promotion.id) !== this.selectedPromotionId);
            },
            get visibleBatches() {
                return this.selectedProduct ? this.selectedProduct.batches : [];
            },
            get referencePrice() {
                if (!this.selectedProduct) {
                    return 0;
                }

                if (this.visibleBatches.length > 0) {
                    return Math.max(...this.visibleBatches.map(batch => Number(batch.selling_price || 0)));
                }

                return Number(this.selectedProduct.selling_price || 0);
            },
            get suggestedSaving() {
                return Math.max(0, this.referencePrice - Number(this.promotionPrice || 0));
            },
            normalized(value) {
                return String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/\s+/g, ' ')
                    .trim();
            },
            loose(value) {
                return this.normalized(value)
                    .replace(/[^a-z0-9]/g, '')
                    .replace(/(.)\1+/g, '$1');
            },
            normalizedBarcode(value) {
                return String(value || '').replace(/\D/g, '');
            },
            productLabel(product) {
                return `${product.name} - stock ${product.quantity} ${product.unit}`;
            },
            async searchProducts() {
                const query = this.productSearch.trim();
                const barcodeQuery = this.normalizedBarcode(query);

                this.searchError = '';

                if (query.length < 2 && barcodeQuery.length < 3) {
                    this.searchError = 'Saisissez au moins 2 caracteres ou 3 chiffres du code-barres.';
                    return;
                }

                this.searchingProducts = true;

                try {
                    const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(query)}`, {
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('Recherche impossible pour le moment.');
                    }

                    const data = await response.json();
                    this.searchResults = data.products || [];
                    this.showSearchResults = true;
                } catch (error) {
                    this.searchError = error.message || 'Recherche impossible pour le moment.';
                } finally {
                    this.searchingProducts = false;
                }
            },
            chooseProduct(product) {
                if (!this.products.some(existingProduct => String(existingProduct.id) === String(product.id))) {
                    this.products.push(product);
                }

                this.selectedProductId = String(product.id);
                this.productSearch = this.productLabel(product);
                this.showSearchResults = false;
            },
            confirmDeletePromotion(promotion) {
                this.pendingDeletePromotion = promotion;
                this.deletePromotionError = '';
            },
            cancelDeletePromotion() {
                if (this.deletingPromotion) {
                    return;
                }

                this.pendingDeletePromotion = null;
                this.deletePromotionError = '';
            },
            async deleteExistingPromotion() {
                if (!this.pendingDeletePromotion) {
                    return;
                }

                this.deletingPromotion = true;
                this.deletePromotionError = '';

                try {
                    const response = await fetch(this.pendingDeletePromotion.delete_url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('La suppression de la promotion a echoue.');
                    }

                    const deletedPromotionId = String(this.pendingDeletePromotion.id);

                    this.products = this.products.map(product => ({
                        ...product,
                        promotions: (product.promotions || []).filter(promotion => String(promotion.id) !== deletedPromotionId),
                    }));

                    this.pendingDeletePromotion = null;
                } catch (error) {
                    this.deletePromotionError = error.message || 'La suppression de la promotion a echoue.';
                } finally {
                    this.deletingPromotion = false;
                }
            },
            formatPrice(amount) {
                return new Intl.NumberFormat('fr-FR').format(Number(amount || 0)) + ' FCFA';
            },
        };
    };
</script>
