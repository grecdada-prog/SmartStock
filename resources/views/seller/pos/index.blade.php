@extends('seller.layouts.app')

@section('title', 'Point de Vente (POS)')

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="posSystem()">
    <!-- Header avec statistiques du jour -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Point de Vente (POS)</h1>
            <p class="mt-2 text-sm text-gray-700">Enregistrez vos ventes rapidement</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-3">
            <a href="{{ route('seller.sales.history') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Historique
            </a>
        </div>
    </div>

    <!-- Statistiques du jour -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ventes aujourd'hui</dt>
                            <dd class="text-2xl font-semibold text-gray-900">{{ $todayStats['sales_count'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">CA du jour</dt>
                            <dd class="text-2xl font-semibold text-gray-900">{{ number_format($todayStats['sales_total'], 0, ',', ' ') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Articles vendus</dt>
                            <dd class="text-2xl font-semibold text-gray-900">{{ number_format($todayStats['items_sold']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interface POS principale -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Panel gauche : Produits -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <!-- Recherche produits -->
                        <div class="flex-1">
                            <input
                                type="text"
                                x-model="searchQuery"
                                @input="searchProducts()"
                                placeholder="Rechercher un produit (nom ou code SKU)..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                            >
                        </div>
                        <!-- Filtre catégorie -->
                        <select x-model="selectedCategory" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                            <option value="">Toutes catégories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Grille de produits -->
                <div class="p-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-[600px] overflow-y-auto">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div
                            @click="addToCart(product)"
                            class="bg-white border-2 border-gray-200 rounded-lg p-3 cursor-pointer hover:border-green-500 hover:shadow-md transition-all duration-200"
                            :class="{'border-green-500': isInCart(product.id)}"
                        >
                            <div class="text-center">
                                <div class="h-12 w-12 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-2">
                                    <span class="text-green-600 font-bold text-lg" x-text="product.name.charAt(0)"></span>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900 truncate" x-text="product.name"></h3>
                                <p class="text-xs text-gray-500" x-text="product.sku"></p>
                                <p class="mt-1 text-lg font-semibold text-green-600" x-text="formatPrice(product.selling_price)"></p>
                                <p class="text-xs text-gray-500">Stock: <span x-text="product.quantity"></span></p>
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
            <div class="bg-white shadow rounded-lg sticky top-4">
                <div class="p-4 bg-green-600 text-white rounded-t-lg">
                    <h2 class="text-lg font-semibold">Panier</h2>
                    <p class="text-sm opacity-90"><span x-text="cart.length"></span> article(s)</p>
                </div>

                <!-- Items du panier -->
                <div class="p-4 max-h-[300px] overflow-y-auto">
                    <template x-if="cart.length === 0">
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="mt-2 text-sm">Panier vide</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="index">
                        <div class="flex items-center space-x-3 mb-3 pb-3 border-b border-gray-200">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900" x-text="item.name"></h4>
                                <p class="text-xs text-gray-500" x-text="formatPrice(item.price) + ' × ' + item.quantity"></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button @click="updateQuantity(index, -1)" class="h-6 w-6 rounded bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                    <span class="text-sm font-bold">-</span>
                                </button>
                                <span class="text-sm font-semibold w-8 text-center" x-text="item.quantity"></span>
                                <button @click="updateQuantity(index, 1)" class="h-6 w-6 rounded bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                    <span class="text-sm font-bold">+</span>
                                </button>
                            </div>
                            <button @click="removeFromCart(index)" class="text-red-600 hover:text-red-800">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <div class="p-4 space-y-3" x-show="cart.length > 0">
                    <!-- Méthode de paiement -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Méthode de paiement</label>
                        <select x-model="paymentMethod" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                            <option value="cash">Espèces</option>
                            <option value="card">Carte</option>
                            <option value="mobile_money">Mobile Money</option>
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

                    <!-- Informations client (optionnel) -->
                    <div>
                        <button @click="showCustomerInfo = !showCustomerInfo" type="button" class="text-sm text-green-600 hover:text-green-700">
                            <span x-show="!showCustomerInfo">+ Ajouter infos client</span>
                            <span x-show="showCustomerInfo">- Masquer infos client</span>
                        </button>
                    </div>

                    <div x-show="showCustomerInfo" class="space-y-2">
                        <input
                            type="text"
                            x-model="customerName"
                            placeholder="Nom du client (optionnel)"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                        >
                        <input
                            type="text"
                            x-model="customerPhone"
                            placeholder="Téléphone (optionnel)"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                        >
                    </div>

                    <!-- Boutons d'action -->
                    <div class="space-y-2 pt-2">
                        <button
                            @click="processSale()"
                            :disabled="processing || cart.length === 0 || (paymentMethod === 'cash' && change < 0)"
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

    <!-- Toast de notification -->
    <div
        x-show="showToast"
        x-transition
        class="fixed bottom-4 right-4 bg-white shadow-lg rounded-lg p-4 max-w-md z-50"
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
        amountReceived: 0,
        change: 0,
        customerName: '',
        customerPhone: '',
        showCustomerInfo: false,

        // UI
        processing: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',

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

        addToCart(product) {
            const existingItem = this.cart.find(item => item.id === product.id);

            if (existingItem) {
                if (existingItem.quantity < product.quantity) {
                    existingItem.quantity++;
                } else {
                    this.showNotification('Stock insuffisant', 'error');
                }
            } else {
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
                this.change = this.amountReceived - this.cartTotal;
            } else {
                this.change = 0;
                this.amountReceived = this.cartTotal;
            }
        },

        formatPrice(amount) {
            return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
        },

        clearCart() {
            if (confirm('Êtes-vous sûr de vouloir vider le panier ?')) {
                this.cart = [];
                this.amountReceived = 0;
                this.change = 0;
                this.customerName = '';
                this.customerPhone = '';
                this.showCustomerInfo = false;
            }
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
                    window.open(receiptUrl, '_blank');

                    // Réinitialiser le panier
                    this.clearCartAfterSale();

                    // Recharger la page après 2 secondes pour mettre à jour les stats
                    setTimeout(() => window.location.reload(), 2000);
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
            this.amountReceived = 0;
            this.change = 0;
            this.customerName = '';
            this.customerPhone = '';
            this.showCustomerInfo = false;
            this.paymentMethod = 'cash';
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
