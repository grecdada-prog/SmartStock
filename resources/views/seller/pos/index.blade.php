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
                                class="block w-full rounded-md border-gray-300 py-2 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            >
                        </div>
                        <!-- Filtre catégorie -->
                        <select x-model="selectedCategory" class="rounded-md border-gray-300 py-2 shadow-sm focus:border-green-500 focus:ring-green-500 sm:w-52 sm:text-sm">
                            <option value="">Toutes catégories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Grille de produits -->
                <div class="grid max-h-[calc(100vh-220px)] min-h-[420px] grid-cols-2 gap-2 overflow-y-auto p-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div
                            @click="product.quantity > 0 && addToCart(product)"
                            class="cursor-pointer rounded-md border border-gray-200 bg-white p-2 transition-all duration-200 hover:border-green-500 hover:shadow-sm"
                            :class="{
                                'border-green-500': isInCart(product.id),
                                'opacity-50 cursor-not-allowed hover:border-gray-200 hover:shadow-none': product.quantity <= 0
                            }"
                        >
                            <div>
                                <div class="mb-1 flex items-start gap-2">
                                    <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded bg-green-100">
                                        <span class="text-xs font-bold text-green-600" x-text="product.name.charAt(0)"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="truncate text-xs font-semibold leading-4 text-gray-900" x-text="product.name" :title="product.name"></h3>
                                        <p class="truncate text-[11px] leading-4 text-gray-500" x-text="product.sku"></p>
                                    </div>
                                </div>
                                <p class="text-sm font-bold leading-5 text-green-600" x-text="formatPrice(product.selling_price)"></p>
                                <p class="text-[11px] leading-4" :class="product.quantity <= 0 ? 'text-red-600 font-medium' : 'text-gray-500'">
                                    Stock: <span x-text="product.quantity"></span>
                                </p>
                                <p x-show="product.quantity <= 0" class="text-[11px] font-medium text-red-600">Indisponible</p>
                            </div>
                        </div>
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
                <div class="shrink-0 bg-green-600 px-3 py-2 text-white">
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
                                <p class="text-xs text-gray-500" x-text="formatPrice(item.price) + ' × ' + item.quantity"></p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="updateQuantity(index, -1)" class="flex h-6 w-6 items-center justify-center rounded bg-gray-200 hover:bg-gray-300">
                                    <span class="text-sm font-bold">-</span>
                                </button>
                                <span class="w-6 text-center text-sm font-semibold" x-text="item.quantity"></span>
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
                            <span class="text-green-600" x-text="formatPrice(cartTotal)"></span>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de paiement -->
                <div class="p-4 space-y-3" x-show="cart.length > 0 || saleCompleted">
                    <!-- Méthode de paiement -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Méthode de paiement</label>
                        <select x-model="paymentMethod" @change="handlePaymentMethodChange()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                            <option value="cash">Espèces</option>
                            <option value="card">Orange Money</option>
                            <option value="mobile_money">MTN Momo</option>
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
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            placeholder="0"
                        >
                    </div>

                    <!-- Monnaie à rendre -->
                    <div x-show="paymentMethod === 'cash' && amountReceived > 0" class="p-3 bg-blue-50 rounded-md">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Monnaie:</span>
                            <span class="text-lg font-semibold" :class="change >= 0 ? 'text-green-600' : 'text-red-600'" x-text="formatPrice(change)"></span>
                        </div>
                    </div>

                    <!-- Informations client -->
                    <div x-show="paymentMethod === 'cash'">
                        <button @click="showCustomerInfo = !showCustomerInfo" type="button" class="text-sm text-green-600 hover:text-green-700">
                            <span x-show="!showCustomerInfo">+ Ajouter infos client</span>
                            <span x-show="showCustomerInfo">- Masquer infos client</span>
                        </button>
                    </div>

                    <div x-show="showCustomerInfo || paymentMethod !== 'cash'" class="space-y-2">
                        <input
                            type="text"
                            x-model="customerName"
                            placeholder="Nom du client (optionnel)"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                        >
                        <input
                            type="text"
                            x-model="customerPhone"
                            :placeholder="paymentMethod === 'cash' ? 'Telephone (optionnel)' : 'Numero telephone obligatoire'"
                            :required="paymentMethod !== 'cash'"
                            placeholder="Téléphone (optionnel)"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                        >
                        <p x-show="paymentMethod !== 'cash'" class="text-xs text-gray-500">
                            Le numero est obligatoire pour Orange Money et MTN Momo.
                        </p>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="space-y-2 pt-2">
                        <button
                            @click="processSale()"
                            :disabled="processing || cart.length === 0 || (paymentMethod === 'cash' && change < 0) || (paymentMethod !== 'cash' && !customerPhone.trim())"
                            class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
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

                        <button
                            @click="clearCart()"
                            :disabled="processing"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
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
            class="w-full max-w-md rounded-lg border border-green-200 bg-green-50 p-5 shadow-2xl"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-green-900">Vente enregistree</h3>
                    <p class="mt-1 text-sm font-medium text-green-800">
                        Vente <span x-text="lastInvoiceNumber"></span> enregistree.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-3">
                <button
                    type="button"
                    @click="openReceipt()"
                    class="inline-flex min-h-14 items-center justify-center rounded-md border border-green-600 bg-white px-3 py-2 text-sm font-semibold text-green-700 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    Imprimer
                </button>
                <button
                    type="button"
                    @click="openReceipt()"
                    class="inline-flex min-h-14 items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    PDF
                </button>
                <button
                    type="button"
                    @click="startNewSale()"
                    class="inline-flex min-h-14 items-center justify-center rounded-md border border-transparent bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    Nouvelle vente
                </button>
            </div>
        </div>
    </div>

    <!-- Toast de notification -->
    <div
        x-show="showToast"
        x-transition
        class="fixed top-4 left-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-lg bg-white p-4 shadow-xl ring-1 ring-gray-200"
        style="display: none;"
    >
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg x-show="toastType === 'success'" class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg x-show="toastType === 'error'" class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-gray-900" x-text="toastMessage"></p>
            </div>
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

                const data = await response.json();

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

        addToCart(product) {
            const existingItem = this.cart.find(item => item.id === product.id);

            if (existingItem) {
                if (existingItem.quantity < product.quantity) {
                    existingItem.quantity++;
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
                    price: product.selling_price,
                    quantity: 1,
                    maxQuantity: product.quantity
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
                this.calculateChange();
            } else {
                this.showNotification('Stock insuffisant', 'error');
            }
        },

        isInCart(productId) {
            return this.cart.some(item => item.id === productId);
        },

        get cartTotal() {
            return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        },

        calculateChange() {
            if (this.paymentMethod === 'cash') {
                this.change = Number(this.amountReceived || 0) - this.cartTotal;
            } else {
                this.change = 0;
            }
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
                customer_phone: this.customerPhone || null,
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

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Vente enregistrée avec succès!', 'success');

                    // Ouvrir le reçu dans un nouvel onglet
                    const receiptUrl = '{{ route("seller.pos.receipt", ":id") }}'.replace(':id', data.sale_id);
                    this.lastReceiptUrl = receiptUrl;
                    this.lastInvoiceNumber = data.invoice_number || '';
                    this.saleCompleted = true;

                    // Réinitialiser le panier
                    this.clearCartAfterSale();
                    window.dispatchEvent(new CustomEvent('smartstock:refresh-now'));
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

        clearCartAfterSale() {
            this.cart = [];
            this.amountReceived = '';
            this.change = 0;
            this.customerName = '';
            this.customerPhone = '';
            this.showCustomerInfo = false;
            this.paymentMethod = 'cash';
        },

        openReceipt() {
            if (this.lastReceiptUrl) {
                if (window.SmartStockModalLinks?.open) {
                    window.SmartStockModalLinks.open(this.lastReceiptUrl, 'Facture');
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
            setTimeout(() => this.showToast = false, 3000);
        }
    }
}
</script>
@endsection
