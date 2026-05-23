@php
    $productPayload = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->unit,
            'selling_price' => (float) $product->selling_price,
            'quantity' => $product->quantity,
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
        selectedProductId: @js((string) old('product_id', $promotion->product_id)),
        promotionPrice: @js(old('promotion_price', $promotion->promotion_price)),
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
                <select
                    id="product_id"
                    name="product_id"
                    x-model="selectedProductId"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                >
                    <option value="">Selectionner un produit</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} - stock {{ $product->quantity }} {{ $product->unit }}</option>
                    @endforeach
                </select>
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
                        value="{{ old('min_quantity', $promotion->min_quantity ?? 2) }}"
                        required
                        min="2"
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
                <p class="mt-1 text-xs text-gray-500">Une seule promotion active est gardee par produit. Activer celle-ci suspend les autres du meme produit.</p>
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
                            SKU <span x-text="selectedProduct.sku"></span> - stock
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
                </div>
            </template>
            <template x-if="!selectedProduct">
                <p class="mt-4 text-sm text-gray-500">Choisissez un produit pour voir ses prix par lot.</p>
            </template>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('manager.promotions.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</a>
        <button type="submit" class="rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
            {{ $submitLabel }}
        </button>
    </div>
</form>

<script>
    window.promotionForm = function ({ products, selectedProductId, promotionPrice }) {
        return {
            products,
            selectedProductId: String(selectedProductId || ''),
            promotionPrice: Number(promotionPrice || 0),
            get selectedProduct() {
                return this.products.find(product => String(product.id) === String(this.selectedProductId)) || null;
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
            formatPrice(amount) {
                return new Intl.NumberFormat('fr-FR').format(Number(amount || 0)) + ' FCFA';
            },
        };
    };
</script>
