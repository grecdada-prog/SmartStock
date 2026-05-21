@extends('seller.layouts.app')

@section('title', 'Point de Vente')

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="posSystem()">
    <div class="mb-4">
        <h1 class="text-2xl font-semibold text-gray-900">Point de Vente</h1>
        <p class="mt-1 text-sm text-gray-700">Enregistrez vos ventes rapidement</p>
    </div>

    <!-- Interface POS principale -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Panel gauche : Produits -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="p-3 border-b border-gray-200">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <!-- Recherche produits -->
                        <div class="flex-1">
                            <input
                                type="text"
                                x-model="searchQuery"
                                @input="searchProducts()"
                                placeholder="Rechercher un produit (nom ou code SKU)..."
                                class="block w-full rounded-md border-gray-300 py-2 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                            >
                        </div>
                        <!-- Filtre catégorie -->
                        <select x-model="selectedCategory" class="rounded-md border-gray-300 py-2 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:w-52 sm:text-sm">
                            <option value="">Toutes catégories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Liste de produits -->
                <div class="max-h-[calc(100vh-220px)] min-h-[420px] divide-y divide-gray-100 overflow-y-auto">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button
                            type="button"
                            @click="product.quantity > 0 && addToCart(product)"
                            :disabled="product.quantity <= 0"
                            class="flex w-full items-center gap-3 px-3 py-2 text-left transition-colors duration-150 hover:bg-rose-50/60 disabled:cursor-not-allowed"
                            :class="{
                                'bg-rose-50': isInCart(product.id),
                                'opacity-55': product.quantity <= 0
                            }"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex min-w-0 flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-2">
                                    <span class="truncate text-sm font-semibold text-gray-900" x-text="product.name" :title="product.name"></span>
                                    <span class="hidden text-gray-300 sm:inline">-</span>
                                    <span class="shrink-0 text-sm font-bold text-rose-600" x-text="formatPrice(productDisplayPrice(product))"></span>
                                    <span x-show="product.quantity <= 0" class="shrink-0 text-xs font-medium text-red-600">Indisponible</span>
                                </div>
                            </div>

                            <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                Stock disponible: <span x-text="product.quantity"></span>
                            </span>
                        </button>
                    </template>

                    <template x-if="filteredProducts.length === 0">
                        <div class="col-span-full text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-2">Aucun produit trouvé</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Panel droit : Panier et paiement -->
        <div class="lg:col-span-1">
            <div class="sticky flex max-h-[calc(100vh-6rem)] flex-col overflow-hidden rounded-lg bg-white shadow" style="top: 5rem;">
                <div class="shrink-0 bg-rose-600 px-3 py-2 text-white">
                    <h2 class="text-base font-semibold">Panier</h2>
                    <p class="text-sm opacity-90"><span x-text="cart.length"></span> article(s)</p>
                </div>

                <!-- Items du panier -->
                <div class="min-h-0 flex-1 overflow-y-auto p-3">
                    <template x-if="cart.length === 0">
                        <div class="py-5 text-center text-gray-500">
                            <svg class="mx-auto h-9 w-9 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="mt-2 text-sm">Panier vide</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="index">
                        <div class="mb-2 flex items-center gap-2 border-b border-gray-200 pb-2">
                            <div class="min-w-0 flex-1">
                                <h4 class="truncate text-xs font-semibold text-gray-900" x-text="item.name" :title="item.name"></h4>
                                <div class="space-y-0.5 text-xs text-gray-500">
                                    <template x-for="(line, lineIndex) in item.priceLines" :key="lineIndex">
                                        <p x-text="formatPrice(line.price) + ' x ' + line.quantity"></p>
                                    </template>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="updateQuantity(index, -1)" class="flex h-6 w-6 items-center justify-center rounded bg-gray-200 hover:bg-gray-300">
                                    <span class="text-sm font-bold">-</span>
                                </button>
                                <input
                                    type="number"
                                    min="0"
                                    :max="item.maxQuantity"
                                    step="1"
                                    x-model.number="item.quantity"
                                    @change="setCartQuantity(index, item.quantity)"
                                    @keydown.enter.prevent="$event.target.blur()"
                                    class="h-7 w-14 rounded-md border-gray-300 px-1 text-center text-sm font-semibold shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                >
                                <button @click="updateQuantity(index, 1)" class="flex h-6 w-6 items-center justify-center rounded bg-gray-200 hover:bg-gray-300">
                                    <span class="text-sm font-bold">+</span>
                                </button>
                            </div>
                            <button @click="removeFromCart(index)" class="text-red-600 hover:text-red-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Total -->
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    <div class="space-y-2">
                        <div class="flex justify-between text-lg font-semibold">
                            <span>Total:</span>
                            <span class="text-rose-600" x-text="formatPrice(cartTotal)"></span>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de paiement -->
                <div class="p-4 space-y-3" x-show="cart.length > 0 || saleCompleted">
                    <!-- Méthode de paiement -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Méthode de paiement</label>
                        <select x-model="paymentMethod" @change="handlePaymentMethodChange()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                            <option value="cash">Espèces</option>
                            <option value="mobile_money">Paiement mobile</option>
                        </select>
                    </div>

                    <!-- Montant reçu (seulement pour espèces) -->
                    <div x-show="paymentMethod === 'cash'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Montant reçu</label>
                        <input
                            type="number"
                            x-model.number="amountReceived"
                            @input="calculateChange()"
                            step="100"
                            min="0"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                            placeholder="0"
                        >
                    </div>

                    <!-- Monnaie à rendre -->
                    <div x-show="paymentMethod === 'cash' && amountReceived > 0" class="p-3 bg-blue-50 rounded-md">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Monnaie:</span>
                            <span class="text-lg font-semibold" :class="change >= 0 ? 'text-rose-600' : 'text-red-600'" x-text="formatPrice(change)"></span>
                        </div>
                    </div>

                    <!-- Informations client -->
                    <div x-show="paymentMethod === 'cash'">
                        <button @click="showCustomerInfo = !showCustomerInfo" type="button" class="text-sm text-rose-600 hover:text-rose-700">
                            <span x-show="!showCustomerInfo">+ Ajouter infos client</span>
                            <span x-show="showCustomerInfo">- Masquer infos client</span>
                        </button>
                    </div>

                    <div x-show="showCustomerInfo || paymentMethod !== 'cash'" class="space-y-2">
                        <input
                            type="text"
                            x-model="customerName"
                            placeholder="Nom du client (optionnel)"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                        >
                        <input
                            type="tel"
                            x-model="customerPhone"
                            data-phone-format
                            @smartstore:phone-formatted="customerPhone = $event.target.value"
                            :placeholder="paymentMethod === 'cash' ? 'Telephone (optionnel)' : 'Numero telephone obligatoire'"
                            :required="paymentMethod !== 'cash'"
                            placeholder="Téléphone (optionnel)"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                        >
                        <p x-show="paymentMethod !== 'cash'" class="text-xs" :class="mobileOperator ? 'text-gray-500' : 'text-red-600'">
                            <span x-text="mobileOperator ? ('Operateur detecte : ' + mobileOperatorLabel) : 'Numero Orange Money ou MTN Momo Cameroun obligatoire.'"></span>
                        </p>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="space-y-2 pt-2">
                        <button
                            @click="processSale()"
                            :disabled="processing || cart.length === 0 || (paymentMethod === 'cash' && change < 0) || (paymentMethod !== 'cash' && (!customerPhone.trim() || !mobileOperator))"
                            class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent text-base font-medium rounded-md text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg x-show="!processing" class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg x-show="processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="processing ? 'Traitement...' : 'Enregistrer la vente'"></span>
                        </button>
                        <p x-show="paymentMethod === 'cash' && cart.length > 0 && change < 0" class="text-sm text-red-600 text-center">
                            Montant recu insuffisant.
                        </p>
                        <p x-show="paymentMethod !== 'cash' && cart.length > 0 && !customerPhone.trim()" class="text-sm text-red-600 text-center">
                            Renseignez le numero de telephone pour ce paiement.
                        </p>
                        <p x-show="paymentMethod !== 'cash' && cart.length > 0 && customerPhone.trim() && !mobileOperator" class="text-sm text-red-600 text-center">
                            Numero non reconnu pour Orange Money ou MTN Momo Cameroun.
                        </p>

                        <button
                            @click="clearCart()"
                            :disabled="processing"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 disabled:opacity-50"
                        >
                            Vider le panier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de vente terminee -->
    <div
        x-show="saleCompleted"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/45 px-4 py-6"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="saleCompleted"
            x-transition
            @click.outside.stop
            class="w-full max-w-md rounded-lg border border-rose-200 bg-rose-50 p-5 shadow-2xl"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-rose-100">
                    <svg class="h-6 w-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-rose-900">Vente enregistree</h3>
                    <p class="mt-1 text-sm font-medium text-rose-800">
                        Vente <span x-text="lastInvoiceNumber"></span> enregistree.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-3">
                <button
                    type="button"
                    @click="openReceipt()"
                    class="inline-flex min-h-14 items-center justify-center rounded-md border border-rose-600 bg-white px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                >
                    Imprimer
                </button>
                <button
                    type="button"
                    @click="openReceipt()"
                    class="inline-flex min-h-14 items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                >
                    PDF
                </button>
                <button
                    type="button"
                    @click="startNewSale()"
                    class="inline-flex min-h-14 items-center justify-center rounded-md border border-transparent bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                >
                    Nouvelle vente
                </button>
            </div>
        </div>
    </div>

    <!-- Modal attente paiement mobile -->
    <div
        x-show="mobilePaymentWaiting"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4 py-6"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="mobilePaymentWaiting"
            x-transition
            @click.outside.stop
            class="w-full max-w-md rounded-lg bg-white p-5 shadow-2xl"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-rose-100">
                    <svg class="h-6 w-6 animate-spin text-rose-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-gray-900">Confirmation du paiement</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Demande envoyee par USSD au <span class="font-semibold" x-text="customerPhone"></span>.
                    </p>
                    <p class="mt-2 text-sm text-gray-600">
                        <span x-text="mobileOperatorLabel"></span> attend la validation du client.
                    </p>
                    <p x-show="mobilePaymentMessage" class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700" x-text="mobilePaymentMessage"></p>
                </div>
            </div>

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    @click="cancelMobilePayment()"
                    :disabled="mobilePaymentCanceling"
                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-wait disabled:opacity-60"
                >
                    <span x-text="mobilePaymentCanceling ? 'Annulation...' : 'Annuler la transaction'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Notification locale POS -->
    <div
        x-show="showToast"
        x-transition
        class="fixed top-3 left-1/2 z-[9999] w-[calc(100%-1.5rem)] max-w-md -translate-x-1/2 rounded-md border bg-white px-4 py-3 text-gray-900 shadow-lg"
        :class="toastType === 'success' ? 'border-rose-300' : 'border-red-300'"
        style="display: none;"
        role="alert"
    >
        <div class="flex items-start gap-3">
            <div class="mt-0.5 shrink-0" :class="toastType === 'success' ? 'text-rose-600' : 'text-red-600'">
                <svg x-show="toastType === 'success'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M5 13l4 4L19 7" />
                </svg>
                <svg x-show="toastType === 'error'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 9v4m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm leading-5" x-text="toastMessage"></p>
            </div>
            <button type="button"
                    @click="showToast = false"
                    class="-mr-1 -mt-1 rounded-md p-1.5 text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="toastType === 'success' ? 'focus:ring-rose-600' : 'focus:ring-red-600'"
                    aria-label="Fermer le message">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function posSystem() {
    return {
        // Produits et filtres
        allProducts: @json($products),
        filteredProducts: @json($products),
        searchQuery: '',
        selectedCategory: '',
        // Panier
        cart: [],

        // Paiement
        paymentMethod: 'cash',
        amountReceived: '',
        change: 0,
        customerName: '',
        customerPhone: '',
        showCustomerInfo: false,
        mobilePaymentWaiting: false,
        mobilePaymentCanceling: false,
        mobilePaymentRef: '',
        mobilePaymentMessage: '',

        // UI
        processing: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        saleCompleted: false,
        lastReceiptUrl: '',
        lastInvoiceNumber: '',

        init() {
            this.$watch('selectedCategory', () => this.filterProducts());
            this.$watch('customerPhone', () => this.detectMobileOperator());
            window.addEventListener('smartstock:mobile-payment-confirmed', (event) => this.handleMobilePaymentConfirmed(event.detail || {}));
            window.addEventListener('smartstock:mobile-payment-failed', (event) => this.handleMobilePaymentFailed(event.detail || {}));
        },

        searchProducts() {
            this.filterProducts();
        },

        filterProducts() {
            let products = this.allProducts;

            // Filtre par catégorie
            if (this.selectedCategory) {
                products = products.filter(p => p.category_id == this.selectedCategory);
            }

            // Filtre par recherche
            if (this.searchQuery.length > 0) {
                const query = this.searchQuery.toLowerCase();
                products = products.filter(p =>
                    p.name.toLowerCase().includes(query) ||
                    p.sku.toLowerCase().includes(query)
                );
            }

            this.filteredProducts = products;
        },

        async refreshProducts() {
            try {
                const response = await fetch('{{ route("seller.pos.products") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Refresh failed');
                }

                this.allProducts = data.products;
                this.filterProducts();
            } catch (error) {
                this.showNotification('Impossible de rafraichir le stock', 'error');
                console.error('Stock refresh error:', error);
            }
        },

        addToCart(product, quantity = 1) {
            const requestedQuantity = Math.max(1, Math.floor(Number(quantity || 1)));

            if (requestedQuantity > product.quantity) {
                this.showNotification('Stock insuffisant', 'error');
                return;
            }

            const existingItem = this.cart.find(item => item.id === product.id);

            if (existingItem) {
                const newQuantity = existingItem.quantity + requestedQuantity;

                if (newQuantity <= existingItem.maxQuantity) {
                    existingItem.quantity = newQuantity;
                    existingItem.priceLines = this.productPriceLines(existingItem, newQuantity);
                } else {
                    this.showNotification('Stock insuffisant', 'error');
                }
            } else {
                if (product.quantity <= 0) {
                    this.showNotification('Produit indisponible', 'error');
                    return;
                }

                this.cart.push({
                    id: product.id,
                    name: product.name,
                    sku: product.sku,
                    price: this.productDisplayPrice(product),
                    quantity: requestedQuantity,
                    maxQuantity: product.quantity,
                    stockMovements: product.stock_movements || [],
                    priceLines: this.productPriceLines(product, requestedQuantity),
                });
            }

            this.calculateChange();
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.calculateChange();
        },

        updateQuantity(index, delta) {
            const item = this.cart[index];
            const newQuantity = item.quantity + delta;

            if (newQuantity <= 0) {
                this.removeFromCart(index);
            } else if (newQuantity <= item.maxQuantity) {
                item.quantity = newQuantity;
                item.priceLines = this.productPriceLines(item, newQuantity);
                this.calculateChange();
            } else {
                this.showNotification('Stock insuffisant', 'error');
            }
        },

        setCartQuantity(index, quantity) {
            const item = this.cart[index];
            const newQuantity = Math.floor(Number(quantity || 0));

            if (newQuantity <= 0) {
                this.removeFromCart(index);
            } else if (newQuantity <= item.maxQuantity) {
                item.quantity = newQuantity;
                item.priceLines = this.productPriceLines(item, newQuantity);
            } else {
                item.quantity = item.maxQuantity;
                item.priceLines = this.productPriceLines(item, item.maxQuantity);
                this.showNotification('Stock insuffisant', 'error');
            }

            this.calculateChange();
        },

        isInCart(productId) {
            return this.cart.some(item => item.id === productId);
        },

        get cartTotal() {
            return this.cart.reduce((total, item) => {
                return total + item.priceLines.reduce((lineTotal, line) => lineTotal + line.subtotal, 0);
            }, 0);
        },

        calculateChange() {
            if (this.paymentMethod === 'cash') {
                this.change = Number(this.amountReceived || 0) - this.cartTotal;
            } else {
                this.change = 0;
            }
        },

        get mobileOperator() {
            return this.operatorForCameroonPhone(this.customerPhone);
        },

        get mobileOperatorLabel() {
            if (this.mobileOperator === 'CM_ORANGEMONEY') {
                return 'Orange Money';
            }

            if (this.mobileOperator === 'CM_MTNMOBILEMONEY') {
                return 'MTN Momo';
            }

            return 'Paiement mobile';
        },

        detectMobileOperator() {
            return this.mobileOperator;
        },

        operatorForCameroonPhone(phone) {
            let digits = String(phone || '').replace(/\D/g, '');

            if (digits.length === 12 && digits.startsWith('237')) {
                digits = digits.slice(3);
            }

            if (digits.length !== 9 || !digits.startsWith('6')) {
                return null;
            }

            const prefix = Number(digits.slice(0, 3));
            const isMtn = (prefix >= 650 && prefix <= 654) || (prefix >= 670 && prefix <= 679) || (prefix >= 680 && prefix <= 683);
            const isOrange = (prefix >= 655 && prefix <= 659) || (prefix >= 685 && prefix <= 689) || (prefix >= 690 && prefix <= 699);

            if (isMtn) {
                return 'CM_MTNMOBILEMONEY';
            }

            if (isOrange) {
                return 'CM_ORANGEMONEY';
            }

            return null;
        },

        handlePaymentMethodChange() {
            if (this.paymentMethod === 'cash') {
                this.amountReceived = '';
                this.change = -this.cartTotal;
            } else {
                this.change = 0;
                this.showCustomerInfo = true;
            }
        },

        formatPrice(amount) {
            return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
        },

        productDisplayPrice(product) {
            return Number(product.fifo_selling_price ?? product.selling_price ?? 0);
        },

        productPriceLines(product, quantity) {
            let remaining = Math.max(0, Math.floor(Number(quantity || 0)));
            const lines = [];
            const batches = product.stockMovements || product.stock_movements || [];

            batches.forEach(batch => {
                if (remaining <= 0) {
                    return;
                }

                const available = Math.max(0, Math.floor(Number(batch.remaining_quantity || 0)));
                if (available <= 0) {
                    return;
                }

                const taken = Math.min(remaining, available);
                const price = Number(batch.selling_price ?? product.selling_price ?? product.price ?? 0);

                lines.push({
                    quantity: taken,
                    price,
                    subtotal: taken * price,
                });

                remaining -= taken;
            });

            if (remaining > 0) {
                const price = Number(product.selling_price ?? product.price ?? 0);

                lines.push({
                    quantity: remaining,
                    price,
                    subtotal: remaining * price,
                });
            }

            return lines;
        },

        clearCart() {
            this.cart = [];
            this.amountReceived = '';
            this.change = 0;
            this.customerName = '';
            this.customerPhone = '';
            this.showCustomerInfo = false;
            this.saleCompleted = false;
            this.lastReceiptUrl = '';
            this.lastInvoiceNumber = '';
            this.mobilePaymentWaiting = false;
            this.mobilePaymentCanceling = false;
            this.mobilePaymentRef = '';
            this.mobilePaymentMessage = '';
        },

        async processSale() {
            if (this.cart.length === 0) {
                this.showNotification('Le panier est vide', 'error');
                return;
            }

            if (this.paymentMethod === 'cash' && this.change < 0) {
                this.showNotification('Montant reçu insuffisant', 'error');
                return;
            }

            if (this.paymentMethod === 'cash' && this.amountReceived === '') {
                this.showNotification('Renseignez le montant recu', 'error');
                return;
            }

            if (this.paymentMethod !== 'cash' && !this.customerPhone.trim()) {
                this.showCustomerInfo = true;
                this.showNotification('Renseignez le numero de telephone', 'error');
                return;
            }

            if (this.paymentMethod !== 'cash') {
                await this.startMobilePayment();
                return;
            }

            this.processing = true;

            const saleData = {
                items: this.cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity,
                    price: item.price
                })),
                payment_method: this.paymentMethod,
                amount_received: this.paymentMethod === 'cash' ? this.amountReceived : this.cartTotal,
                customer_name: this.customerName || null,
                customer_phone: this.customerPhone ? this.customerPhone.replace(/\D/g, '') : null,
                _token: '{{ csrf_token() }}'
            };

            try {
                const response = await fetch('{{ route("seller.pos.sale") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(saleData)
                });

                const data = await this.parseJsonResponse(response);

                if (data.success) {
                    this.showNotification('Vente enregistrée avec succès!', 'success');

                    // Ouvrir le reçu dans un nouvel onglet
                    const receiptUrl = '{{ route("seller.pos.receipt", ":id") }}'.replace(':id', data.sale_id);
                    this.lastReceiptUrl = receiptUrl;
                    this.lastInvoiceNumber = data.invoice_number || '';
                    this.saleCompleted = true;

                    // Réinitialiser le panier
                    this.clearCartAfterSale();
                    window.dispatchEvent(new CustomEvent('smartstore:refresh-now'));
                } else {
                    this.showNotification(data.message || 'Erreur lors de l\'enregistrement', 'error');
                }
            } catch (error) {
                this.showNotification('Erreur de connexion au serveur', 'error');
                console.error('Error:', error);
            } finally {
                this.processing = false;
            }
        },

        async startMobilePayment() {
            if (!this.mobileOperator) {
                this.showNotification('Numero non reconnu pour Orange Money ou MTN Momo Cameroun.', 'error');
                return;
            }

            this.processing = true;
            this.mobilePaymentMessage = '';

            const paymentData = {
                items: this.cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity,
                })),
                payment_method: 'mobile_money',
                customer_name: this.customerName || null,
                customer_phone: this.customerPhone ? this.customerPhone.replace(/\D/g, '') : null,
                _token: '{{ csrf_token() }}'
            };

            try {
                const response = await fetch('{{ route("seller.pos.mobile-payment.start") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(paymentData)
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Paiement mobile echoue.');
                }

                this.mobilePaymentRef = data.payment_ref || '';
                this.mobilePaymentMessage = data.monetbil_enabled
                    ? 'Le client doit confirmer la demande sur son telephone.'
                    : 'Integration prete : Monetbil est desactive dans la configuration, aucun USSD reel n est envoye.';
                this.mobilePaymentWaiting = true;
            } catch (error) {
                this.showNotification(error.message || 'Paiement mobile echoue.', 'error');
            } finally {
                this.processing = false;
            }
        },

        async cancelMobilePayment() {
            if (!this.mobilePaymentRef) {
                this.mobilePaymentWaiting = false;
                return;
            }

            this.mobilePaymentCanceling = true;

            try {
                const response = await fetch('{{ route("seller.pos.mobile-payment.cancel") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_ref: this.mobilePaymentRef,
                        _token: '{{ csrf_token() }}'
                    })
                });
                const data = await this.parseJsonResponse(response);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Annulation impossible.');
                }

                this.mobilePaymentWaiting = false;
                this.mobilePaymentRef = '';
                this.mobilePaymentMessage = '';
                this.showNotification(data.message || 'Transaction mobile annulee.', 'error');
            } catch (error) {
                this.showNotification(error.message || 'Annulation impossible.', 'error');
            } finally {
                this.mobilePaymentCanceling = false;
            }
        },

        handleMobilePaymentConfirmed(data) {
            this.mobilePaymentWaiting = false;
            this.mobilePaymentRef = '';
            this.mobilePaymentMessage = '';
            this.lastReceiptUrl = data.sale_id ? '{{ route("seller.pos.receipt", ":id") }}'.replace(':id', data.sale_id) : '';
            this.lastInvoiceNumber = data.invoice_number || '';
            this.saleCompleted = true;
            this.clearCartAfterSale();
            window.dispatchEvent(new CustomEvent('smartstore:refresh-now'));
        },

        handleMobilePaymentFailed(data) {
            this.mobilePaymentWaiting = false;
            this.mobilePaymentRef = '';
            this.mobilePaymentMessage = '';
            this.showNotification(data.message || 'Paiement mobile echoue.', 'error');
        },

        async parseJsonResponse(response) {
            let data = {};

            try {
                data = await response.json();
            } catch (error) {
                data = {};
            }

            if ([401, 419].includes(response.status)) {
                this.showNotification(data.message || 'Votre session a expire. Veuillez vous reconnecter.', 'error');

                if (data.redirect) {
                    setTimeout(() => window.location.assign(data.redirect), 1200);
                }

                throw new Error(data.message || 'Votre session a expire.');
            }

            if (response.status === 429) {
                throw new Error(data.message || 'Trop de tentatives. Veuillez patienter quelques secondes puis reessayer.');
            }

            return data;
        },

        clearCartAfterSale() {
            this.cart = [];
            this.amountReceived = '';
            this.change = 0;
            this.customerName = '';
            this.customerPhone = '';
            this.showCustomerInfo = false;
            this.paymentMethod = 'cash';
            this.mobilePaymentWaiting = false;
            this.mobilePaymentCanceling = false;
            this.mobilePaymentRef = '';
            this.mobilePaymentMessage = '';
        },

        openReceipt() {
            if (this.lastReceiptUrl) {
                if (window.SmartStoreModalLinks?.open) {
                    window.SmartStoreModalLinks.open(this.lastReceiptUrl, 'Facture');
                } else {
                    window.location.assign(this.lastReceiptUrl);
                }
            }
        },

        async startNewSale() {
            this.saleCompleted = false;
            this.lastReceiptUrl = '';
            this.lastInvoiceNumber = '';
            this.clearCart();
            await this.refreshProducts();
        },

        showNotification(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.showToast = true;
            setTimeout(() => this.showToast = false, 5000);
        }
    }
}
</script>
@endsection
