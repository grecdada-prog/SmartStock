@extends('seller.layouts.app')

@section('title', 'Point de Vente')

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="posSystem()" x-cloak data-pos-scanner-zone>
    <div class="mb-4">
        @php
            $managerName = \App\Models\User::find(auth()->user()->created_by)?->name ?? 'Boutique';
            $totalProducts = count($products);
        @endphp
        <h1 class="text-2xl font-semibold text-gray-900">
            {{ $managerName }}
            <span class="ml-2 text-base font-normal text-gray-500">({{ $totalProducts }} produit{{ $totalProducts > 1 ? 's' : '' }} disponible{{ $totalProducts > 1 ? 's' : '' }})</span>
        </h1>
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
                                @keydown.enter.prevent.stop="addProductFromSearchBarcode()"
                                placeholder="Rechercher un produit (nom ou code-barres)..."
                                class="block w-full rounded-md border-gray-300 py-2 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm"
                            >
                        </div>
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
                                <span x-text="product.quantity"></span>
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
                <div class="shrink-0 bg-rose-600 px-3 py-2 text-white flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold">Panier</h2>
                        <p class="text-sm opacity-90"><span x-text="cart.length"></span> article(s)</p>
                    </div>
                    <button
                        x-show="cart.length > 0"
                        @click="clearCart()"
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded hover:bg-rose-700 transition-colors"
                        title="Vider le panier"
                        aria-label="Vider le panier"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
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
                                        <p x-text="formatPrice(line.price) + ' x ' + line.quantity + ' = ' + formatPrice(line.subtotal)"></p>
                                    </template>
                                    <div x-show="eligiblePromotions(item).length > 0" class="mt-1 space-y-1">
                                        <template x-for="promotion in eligiblePromotions(item)" :key="promotion.id">
                                            <label class="flex items-center gap-1.5 rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700">
                                                <input
                                                    type="checkbox"
                                                    :value="promotion.id"
                                                    :checked="Number(item.selectedPromotionId) === Number(promotion.id)"
                                                    @change="togglePromotion(index, promotion.id, $event.target.checked)"
                                                    class="h-3.5 w-3.5 border-rose-300 text-rose-600 focus:ring-rose-500"
                                                >
                                                <span>
                                                    Appliquer Prix
                                                    <span x-text="formatPrice(promotion.promotion_price || 0)"></span>
                                                    <span x-text="promotion.name"></span>
                                                </span>
                                            </label>
                                        </template>
                                    </div>
                                    <p x-show="promotionMessage(item)" x-text="promotionMessage(item)" class="text-[11px] text-gray-400">
                                    </p>
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
                        <div x-show="paymentMethod !== 'cash'" x-cloak class="flex justify-between text-sm font-semibold text-gray-700">
                            <span>Frais operateur (2%) :</span>
                            <span x-text="formatPrice(operatorFee)"></span>
                        </div>
                        <div x-show="paymentMethod !== 'cash'" x-cloak class="flex justify-between text-base font-extrabold">
                            <span>Total a payer :</span>
                            <span class="text-rose-600" x-text="formatPrice(totalToPay)"></span>
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
                            <option value="mobile_money">Paiement Mobile</option>
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
                        <div
                            class="flex min-h-11 overflow-hidden rounded-md border bg-white shadow-sm focus-within:ring-1"
                            :class="paymentMethod !== 'cash' && mobileOperator && !mobilePaymentReady ? 'border-red-500 focus-within:border-red-500 focus-within:ring-red-500' : 'border-gray-300 focus-within:border-rose-500 focus-within:ring-rose-500'"
                        >
                            <div
                                x-show="paymentMethod !== 'cash' && mobileOperator"
                                class="flex shrink-0 items-center gap-2 border-r border-gray-200 bg-gray-50 px-3 text-sm font-semibold text-gray-900"
                            >
                                <span
                                    class="h-3.5 w-3.5 rounded-full ring-1 ring-gray-900/20"
                                    :style="'background-color: ' + mobileOperatorColor"
                                    aria-hidden="true"
                                ></span>
                                <span x-text="mobileOperatorLabel"></span>
                            </div>
                        <input
                            type="tel"
                            x-model="customerPhone"
                            data-phone-format
                            @input="limitCameroonPhone()"
                            @smartstore:phone-formatted="customerPhone = $event.target.value; limitCameroonPhone()"
                            :placeholder="paymentMethod === 'cash' ? 'Telephone (optionnel)' : 'Entrez le numero de telephone'"
                            :required="paymentMethod !== 'cash'"
                            maxlength="15"
                            inputmode="numeric"
                            placeholder="Téléphone (optionnel)"
                            class="min-w-0 flex-1 border-0 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:ring-0"
                        >
                        </div>
                        <p x-show="paymentMethod !== 'cash' && !mobileOperator" class="text-xs text-gray-500">
                            Entrez le numero de telephone du client.
                        </p>
                        <p x-show="paymentMethod !== 'cash' && mobileOperator && !mobilePaymentReady" class="text-xs text-red-600">
                            Numero invalide - 9 chiffres requis.
                        </p>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="space-y-2 pt-2">
                        <button
                            @click="processSale()"
                            :disabled="processing || cart.length === 0 || (paymentMethod === 'cash' && change < 0) || (paymentMethod !== 'cash' && !mobilePaymentReady)"
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

    <div
        x-show="paymentTransaction"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/45 px-4 py-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-md rounded-lg border border-rose-200 bg-white p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-rose-600">Paiement Monetbil</p>
                    <h3 class="mt-1 break-words text-lg font-extrabold text-gray-950" x-text="readablePaymentMessage(paymentMessage || 'Paiement en attente')"></h3>
                </div>
                <button type="button" @click="paymentTransaction = null" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
            </div>
            <dl class="mt-4 space-y-2 rounded-lg bg-rose-50 p-4 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-gray-600">Reference</dt><dd class="break-all text-right font-bold" x-text="paymentTransaction?.reference"></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-600">Operateur</dt><dd class="break-words text-right font-bold" x-text="paymentTransaction?.operator_label"></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-600">Total a payer</dt><dd class="font-bold text-rose-600" x-text="formatPrice(paymentTransaction?.total_amount || 0)"></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-600">Statut</dt><dd class="font-bold" x-text="paymentTransaction?.status"></dd></div>
            </dl>
            <p x-show="paymentTransaction?.failure_reason" class="mt-3 break-words rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-700" x-text="readablePaymentMessage(paymentTransaction?.failure_reason)"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="paymentTransaction = null" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">Fermer</button>
                <button type="button" @click="checkMonetbilPayment()" :disabled="processing || !paymentTransaction" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700 disabled:opacity-60">
                    Verifier
                </button>
            </div>
        </div>
    </div>

    <!-- Notification locale POS -->
    <div
        x-show="showToast"
        @click.away="if (toastType !== 'success') showToast = false"
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
                <p class="break-words text-sm leading-5" x-text="readablePaymentMessage(toastMessage)"></p>
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
        // Panier
        cart: [],

        // Paiement
        paymentMethod: 'cash',
        operatorFeeRate: 0.02,
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
        paymentTransaction: null,
        paymentMessage: '',
        paymentCheckTimer: null,
        barcodeScanBuffer: '',
        barcodeScanStartedAt: 0,
        barcodeScanLastKeyAt: 0,
        barcodeScanField: null,
        barcodeScanFieldValueBefore: '',
        barcodeScanHandler: null,
        inactivityRefreshHandler: null,
        inactivityRefreshTimerId: null,
        lastActivityAt: Date.now(),
        lastSilentRefreshAt: 0,
        cartStorageKey: 'smartstore:pos-cart:{{ auth()->id() }}',
        saleTokenStorageKey: 'smartstore:pos-sale-token:{{ auth()->id() }}',
        currentSaleToken: '',

        init() {
            this.ensureSaleToken();
            this.restoreCart();
            this.$watch('customerPhone', () => this.detectMobileOperator());
            this.barcodeScanHandler = (event) => this.handleBarcodeScanKeydown(event);
            window.addEventListener('keydown', this.barcodeScanHandler, true);
            this.initPosInactivityRefresh();
        },

        destroy() {
            if (this.barcodeScanHandler) {
                window.removeEventListener('keydown', this.barcodeScanHandler, true);
            }

            if (this.inactivityRefreshHandler) {
                ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'input', 'change'].forEach((eventName) => {
                    window.removeEventListener(eventName, this.inactivityRefreshHandler, true);
                });
            }

            if (this.inactivityRefreshTimerId) {
                window.clearInterval(this.inactivityRefreshTimerId);
            }
        },

        searchProducts() {
            this.filterProducts();
        },

        filterProducts() {
            let products = this.allProducts;

            // Filtre par catégorie
            // Filtre par recherche
            if (this.searchQuery.length > 0) {
                const query = this.searchQuery.toLowerCase();
                const barcodeQuery = this.normalizeBarcode(this.searchQuery);
                products = products.filter(p =>
                    p.name.toLowerCase().includes(query) ||
                    String(p.barcode || '').toLowerCase().includes(query) ||
                    (barcodeQuery.length > 0 && this.normalizeBarcode(p.barcode).includes(barcodeQuery))
                );
            }

            this.filteredProducts = products;
        },

        async refreshProducts(options = {}) {
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
                this.syncCartWithProducts();
                this.filterProducts();
            } catch (error) {
                if (!options.silent) {
                    this.showNotification('Impossible de rafraichir le stock', 'error');
                }
                console.error('Stock refresh error:', error);
            }
        },

        initPosInactivityRefresh() {
            this.inactivityRefreshHandler = () => {
                this.lastActivityAt = Date.now();
            };

            ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'input', 'change'].forEach((eventName) => {
                window.addEventListener(eventName, this.inactivityRefreshHandler, true);
            });

            this.inactivityRefreshTimerId = window.setInterval(() => this.refreshAfterInactivity(), 10000);
        },

        async refreshAfterInactivity() {
            const inactivityMs = Date.now() - this.lastActivityAt;

            if (inactivityMs < 120000 || this.shouldSkipSilentPosRefresh()) {
                return;
            }

            this.lastSilentRefreshAt = Date.now();
            await this.refreshProducts({ silent: true });
            this.lastActivityAt = Date.now();
        },

        shouldSkipSilentPosRefresh() {
            if (this.processing || this.saleCompleted) {
                return true;
            }

            const active = document.activeElement;

            return Boolean(active && active !== document.body && (
                ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName) || active.isContentEditable
            ));
        },

        ensureSaleToken() {
            try {
                const existingToken = window.localStorage.getItem(this.saleTokenStorageKey);
                this.currentSaleToken = existingToken || this.generateSaleToken();
                window.localStorage.setItem(this.saleTokenStorageKey, this.currentSaleToken);
            } catch (error) {
                this.currentSaleToken = this.generateSaleToken();
            }
        },

        resetSaleToken() {
            this.currentSaleToken = this.generateSaleToken();

            try {
                window.localStorage.setItem(this.saleTokenStorageKey, this.currentSaleToken);
            } catch (error) {
                console.warn('POS sale token persistence unavailable:', error);
            }
        },

        generateSaleToken() {
            if (window.crypto?.randomUUID) {
                return window.crypto.randomUUID();
            }

            return 'sale-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
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
                    this.refreshCartItemPricing(existingItem);
                } else {
                    this.showNotification('Stock insuffisant', 'error');
                }
            } else {
                if (product.quantity <= 0) {
                    this.showNotification('Produit indisponible', 'error');
                    return;
                }

                this.cart.push(this.cartItemFromProduct(product, requestedQuantity));
            }

            this.calculateChange();
            this.persistCart();
        },

        addProductFromSearchBarcode() {
            const normalizedSearch = this.normalizeBarcode(this.searchQuery);

            if (!normalizedSearch) {
                return false;
            }

            const product = this.findProductByBarcode(normalizedSearch);

            if (!product) {
                return false;
            }

            this.addToCart(product, 1);
            this.searchQuery = '';
            this.filterProducts();

            return true;
        },

        handleBarcodeScanKeydown(event) {
            if (event.ctrlKey || event.metaKey || event.altKey || event.isComposing) {
                return;
            }

            const now = Date.now();
            const scanGapMs = 500;
            const scanEndGapMs = 1000;
            const minimumScanDigits = 8;

            if (event.key === 'Enter') {
                const bufferedBarcode = this.normalizeBarcode(this.barcodeScanBuffer);
                const bufferedProduct = this.findProductByBarcode(bufferedBarcode);
                const isRecentBufferedInput = this.barcodeScanBuffer.length >= minimumScanDigits
                    && now - this.barcodeScanLastKeyAt <= scanEndGapMs;
                const isScan = Boolean(bufferedProduct) || isRecentBufferedInput;
                const activeValue = this.editableScanTarget(event.target) ? event.target.value : '';
                const activeBarcode = this.normalizeBarcode(activeValue);

                if (!isScan && activeBarcode && this.findProductByBarcode(activeBarcode)) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.resetBarcodeScan();

                    if (event.target === document.activeElement) {
                        event.target.value = '';
                        event.target.dispatchEvent(new Event('input', { bubbles: true }));
                    }

                    this.addProductByScannedBarcode(activeBarcode);
                    return;
                }

                if (isScan) {
                    event.preventDefault();
                    event.stopPropagation();

                    const scannedBarcode = bufferedBarcode;
                    this.restoreFocusedFieldAfterScan();
                    this.resetBarcodeScan();
                    this.addProductByScannedBarcode(scannedBarcode);
                    return;
                }

                this.resetBarcodeScan();
                return;
            }

            if (/^\d$/.test(event.key)) {
                if (!this.barcodeScanBuffer || now - this.barcodeScanLastKeyAt > scanGapMs) {
                    this.barcodeScanBuffer = '';
                    this.barcodeScanStartedAt = now;
                    this.barcodeScanField = this.editableScanTarget(event.target) ? event.target : null;
                    this.barcodeScanFieldValueBefore = this.barcodeScanField ? this.barcodeScanField.value : '';
                }

                this.barcodeScanBuffer += event.key;
                this.barcodeScanLastKeyAt = now;
                return;
            }

            if (event.key.length === 1) {
                this.resetBarcodeScan();
            }
        },

        editableScanTarget(target) {
            if (!target) {
                return false;
            }

            if (target.isContentEditable) {
                return true;
            }

            return ['INPUT', 'TEXTAREA'].includes(target.tagName);
        },

        restoreFocusedFieldAfterScan() {
            const field = this.barcodeScanField;

            if (!field || field !== document.activeElement || field.value === this.barcodeScanFieldValueBefore) {
                return;
            }

            field.value = this.barcodeScanFieldValueBefore;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        },

        resetBarcodeScan() {
            this.barcodeScanBuffer = '';
            this.barcodeScanStartedAt = 0;
            this.barcodeScanLastKeyAt = 0;
            this.barcodeScanField = null;
            this.barcodeScanFieldValueBefore = '';
        },

        normalizeBarcode(value) {
            return String(value || '').replace(/\D/g, '');
        },

        addProductByScannedBarcode(scannedBarcode) {
            const normalizedScan = this.normalizeBarcode(scannedBarcode);
            const product = this.findProductByBarcode(normalizedScan);

            if (!product) {
                this.showNotification('Aucun produit trouve pour ce code-barres', 'error');
                return;
            }

            this.addToCart(product, 1);
        },

        findProductByBarcode(barcode) {
            const normalizedBarcode = this.normalizeBarcode(barcode);

            if (!normalizedBarcode) {
                return null;
            }

            return this.allProducts.find(item => this.normalizeBarcode(item.barcode) === normalizedBarcode) || null;
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.calculateChange();
            this.persistCart();
        },

        updateQuantity(index, delta) {
            const item = this.cart[index];
            const newQuantity = item.quantity + delta;

            if (newQuantity <= 0) {
                this.removeFromCart(index);
            } else if (newQuantity <= item.maxQuantity) {
                item.quantity = newQuantity;
                this.refreshCartItemPricing(item);
                this.calculateChange();
                this.persistCart();
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
                this.refreshCartItemPricing(item);
            } else {
                item.quantity = item.maxQuantity;
                this.refreshCartItemPricing(item);
                this.showNotification('Stock insuffisant', 'error');
            }

            this.calculateChange();
            this.persistCart();
        },

        isInCart(productId) {
            return this.cart.some(item => item.id === productId);
        },

        get cartTotal() {
            return this.cart.reduce((total, item) => {
                return total + item.priceLines.reduce((lineTotal, line) => lineTotal + line.subtotal, 0);
            }, 0);
        },

        get operatorFee() {
            return this.paymentMethod !== 'cash' ? Math.round(this.cartTotal * this.operatorFeeRate) : 0;
        },

        get totalToPay() {
            return this.cartTotal + this.operatorFee;
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

            return 'MTN Momo';
        },

        get mobileOperatorColor() {
            if (this.mobileOperator === 'CM_ORANGEMONEY') {
                return '#f97316';
            }

            if (this.mobileOperator === 'CM_MTNMOBILEMONEY') {
                return '#facc15';
            }

            return 'transparent';
        },

        get mobilePhoneDigits() {
            let digits = String(this.customerPhone || '').replace(/\D/g, '');

            if (digits.length > 3 && digits.startsWith('237')) {
                digits = digits.slice(3);
            }

            return digits;
        },

        get mobilePaymentReady() {
            return this.mobilePhoneDigits.length === 9 && Boolean(this.mobileOperator);
        },

        limitCameroonPhone() {
            let digits = String(this.customerPhone || '').replace(/\D/g, '');
            const hasCountryCode = digits.startsWith('237');
            const maxLength = hasCountryCode ? 12 : 9;

            digits = digits.slice(0, maxLength);
            const groups = [];

            for (let index = 0; index < digits.length; index += 3) {
                groups.push(digits.slice(index, index + 3));
            }

            this.customerPhone = groups.join(' ');
        },

        detectMobileOperator() {
            return this.mobileOperator;
        },

        operatorForCameroonPhone(phone) {
            let digits = String(phone || '').replace(/\D/g, '');

            if (digits.length > 3 && digits.startsWith('237')) {
                digits = digits.slice(3);
            }

            if (digits.length < 3 || !digits.startsWith('6')) {
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

        productPriceLines(product, quantity, selectedPromotionId = null) {
            let remaining = Math.max(0, Math.floor(Number(quantity || 0)));
            const lines = [];
            const batches = product.stockMovements || product.stock_movements || [];
            const promotion = this.promotionById(product, selectedPromotionId);
            const promotionApplies = promotion && remaining >= Number(promotion.min_quantity || 0);

            if (promotionApplies) {
                const price = Number(promotion.promotion_price || 0);

                return [{
                    quantity: remaining,
                    price,
                    subtotal: remaining * price,
                    promotion: true,
                }];
            }

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

        cartItemFromProduct(product, quantity) {
            const safeQuantity = Math.max(1, Math.min(
                Math.floor(Number(quantity || 1)),
                Math.max(1, Math.floor(Number(product.quantity || 1)))
            ));

            return {
                id: product.id,
                name: product.name,
                sku: product.sku,
                price: this.productDisplayPrice(product),
                quantity: safeQuantity,
                maxQuantity: product.quantity,
                stockMovements: product.stock_movements || [],
                activePromotions: product.active_promotions || product.activePromotions || (product.active_promotion ? [product.active_promotion] : []),
                priceLines: this.productPriceLines(product, safeQuantity),
                activePromotion: product.active_promotion || product.activePromotion || null,
                applyPromotion: false,
                selectedPromotionId: null,
            };
        },

        activePromotionsForItem(item) {
            return item.activePromotions || item.active_promotions || (item.activePromotion ? [item.activePromotion] : []);
        },

        eligiblePromotions(item) {
            return this.activePromotionsForItem(item).filter((promotion) => {
                return Number(item.quantity || 0) >= Number(promotion.min_quantity || 0);
            });
        },

        nextPromotion(item) {
            return this.activePromotionsForItem(item).find((promotion) => {
                return Number(item.quantity || 0) < Number(promotion.min_quantity || 0);
            }) || null;
        },

        promotionMessage(item) {
            const promotions = this.activePromotionsForItem(item)
                .slice()
                .sort((first, second) => Number(first.min_quantity || 0) - Number(second.min_quantity || 0));

            if (promotions.length === 0) {
                return '';
            }

            if (promotions.length === 1 || this.eligiblePromotions(item).length === 0) {
                return 'Produit en Promotion';
            }

            const next = promotions.find((promotion) => {
                return Number(item.quantity || 0) < Number(promotion.min_quantity || 0);
            }) || null;

            return next ? `Prochaine promo a partir de ${next.min_quantity} articles.` : 'Produit en Promotion';
        },

        promotionById(item, promotionId) {
            if (!promotionId) {
                return null;
            }

            return this.activePromotionsForItem(item).find((promotion) => {
                return Number(promotion.id) === Number(promotionId);
            }) || null;
        },

        promotionEligible(item) {
            return Boolean(this.promotionById(item, item.selectedPromotionId))
                && Number(item.quantity || 0) >= Number(this.promotionById(item, item.selectedPromotionId)?.min_quantity || 0);
        },

        refreshCartItemPricing(item) {
            if (!this.promotionEligible(item)) {
                item.applyPromotion = false;
                item.selectedPromotionId = null;
            } else {
                item.applyPromotion = true;
            }

            item.priceLines = this.productPriceLines(item, item.quantity, item.selectedPromotionId);
        },

        togglePromotion(index, promotionId = null, checked = null) {
            const item = this.cart[index];

            if (!item) {
                return;
            }

            if (promotionId !== null) {
                if (checked) {
                    item.selectedPromotionId = Number(promotionId);
                    item.applyPromotion = true;
                } else if (Number(item.selectedPromotionId) === Number(promotionId)) {
                    item.selectedPromotionId = null;
                    item.applyPromotion = false;
                }
            }

            this.refreshCartItemPricing(item);
            this.calculateChange();
            this.persistCart();
        },

        persistCart() {
            try {
                const payload = this.cart.map(item => ({
                    id: item.id,
                    quantity: item.quantity,
                    applyPromotion: Boolean(item.applyPromotion),
                    selectedPromotionId: item.selectedPromotionId,
                }));

                if (payload.length === 0) {
                    window.localStorage.removeItem(this.cartStorageKey);
                    return;
                }

                window.localStorage.setItem(this.cartStorageKey, JSON.stringify(payload));
            } catch (error) {
                console.warn('POS cart persistence unavailable:', error);
            }
        },

        restoreCart() {
            try {
                const rawCart = window.localStorage.getItem(this.cartStorageKey);

                if (!rawCart) {
                    return;
                }

                const savedCart = JSON.parse(rawCart);

                if (!Array.isArray(savedCart)) {
                    window.localStorage.removeItem(this.cartStorageKey);
                    return;
                }

                this.cart = savedCart
                    .map(savedItem => {
                        const product = this.allProducts.find(item => item.id === savedItem.id);

                        if (!product || product.quantity <= 0) {
                            return null;
                        }

                        const item = this.cartItemFromProduct(product, savedItem.quantity);
                        item.selectedPromotionId = savedItem.selectedPromotionId || (savedItem.applyPromotion ? item.activePromotion?.id : null);
                        item.applyPromotion = Boolean(item.selectedPromotionId) && this.promotionEligible(item);
                        this.refreshCartItemPricing(item);

                        return item;
                    })
                    .filter(Boolean);

                this.calculateChange();
                this.persistCart();
            } catch (error) {
                window.localStorage.removeItem(this.cartStorageKey);
                console.warn('POS cart restore failed:', error);
            }
        },

        syncCartWithProducts() {
            this.cart = this.cart
                .map(item => {
                    const product = this.allProducts.find(product => product.id === item.id);

                    if (!product || product.quantity <= 0) {
                        return null;
                    }

                    const refreshedItem = this.cartItemFromProduct(product, item.quantity);
                    refreshedItem.selectedPromotionId = item.selectedPromotionId;
                    refreshedItem.applyPromotion = Boolean(refreshedItem.selectedPromotionId) && this.promotionEligible(refreshedItem);
                    this.refreshCartItemPricing(refreshedItem);

                    return refreshedItem;
                })
                .filter(Boolean);

            this.calculateChange();
            this.persistCart();
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
            this.resetSaleToken();
            this.persistCart();
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
                    price: item.price,
                    apply_promotion: Boolean(item.applyPromotion && this.promotionEligible(item)),
                    promotion_id: item.applyPromotion && this.promotionEligible(item) ? item.selectedPromotionId : null,
                })),
                payment_method: this.paymentMethod,
                amount_received: this.paymentMethod === 'cash' ? this.amountReceived : this.totalToPay,
                customer_name: this.customerName || null,
                customer_phone: this.customerPhone ? this.customerPhone.replace(/\D/g, '') : null,
                client_sale_token: this.currentSaleToken,
                _token: '{{ csrf_token() }}'
            };

            try {
                // if (this.paymentMethod !== 'cash') {
                //     await this.startMonetbilPayment(saleData);
                //     return;
                // }

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
                this.showNotification(error.message || 'Erreur de connexion au serveur', 'error');
                console.error('Error:', error);
            } finally {
                this.processing = false;
            }
        },

        async startMonetbilPayment(saleData) {
            const response = await fetch('{{ route("seller.pos.mobile-payment.start") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(saleData)
            });

            const data = await this.parseJsonResponse(response);

            if (!data.success) {
                this.showNotification(data.message || 'Paiement mobile refuse', 'error');
                return;
            }

            this.paymentTransaction = data.transaction;
            this.paymentMessage = data.message || 'Paiement en attente de confirmation Monetbil.';
            this.showNotification('Paiement lance. Demandez au client de confirmer sur son telephone.', 'success');
            this.schedulePaymentCheck();
        },

        schedulePaymentCheck() {
            if (this.paymentCheckTimer) {
                clearTimeout(this.paymentCheckTimer);
            }

            this.paymentCheckTimer = setTimeout(() => this.checkMonetbilPayment(), 5000);
        },

        async checkMonetbilPayment() {
            if (!this.paymentTransaction?.id) {
                return;
            }

            this.processing = true;

            try {
                const url = '{{ route("seller.payments.check", ":id") }}'.replace(':id', this.paymentTransaction.id);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                const data = await this.parseJsonResponse(response);

                this.paymentTransaction = data.transaction || this.paymentTransaction;
                this.paymentMessage = data.message || this.paymentMessage;

                if (data.status === 'success' && data.sale_id) {
                    this.showNotification('Paiement confirme. Vente enregistree avec succes!', 'success');
                    this.lastReceiptUrl = data.receipt_url || '{{ route("seller.pos.receipt", ":id") }}'.replace(':id', data.sale_id);
                    this.lastInvoiceNumber = data.invoice_number || '';
                    this.saleCompleted = true;
                    this.paymentTransaction = null;
                    this.clearCartAfterSale();
                    window.dispatchEvent(new CustomEvent('smartstore:refresh-now'));
                    return;
                }

                if (['failed', 'expired', 'paid_action_required'].includes(data.status)) {
                    this.showNotification(data.message || 'Paiement non finalise', 'error');
                    return;
                }

                this.schedulePaymentCheck();
            } catch (error) {
                this.showNotification('Impossible de verifier le paiement pour le moment', 'error');
                console.error('Monetbil check error:', error);
            } finally {
                this.processing = false;
            }
        },

        readablePaymentMessage(message) {
            const value = String(message || '');

            if (value === 'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED') {
                return 'Solde insuffisant, limite atteinte ou paiement non autorise.';
            }

            return value;
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
                window.SmartStorePosStorage?.clear?.();

                if (data.redirect) {
                    setTimeout(() => window.location.assign(data.redirect), 1200);
                }

                throw new Error(data.message || 'Votre session a expire.');
            }

            if (response.status === 429) {
                throw new Error(data.message || 'Trop de tentatives. Veuillez patienter quelques secondes puis reessayer.');
            }

            if (!response.ok && !data.message && data.errors) {
                const firstErrors = Object.values(data.errors).flat();
                data.message = firstErrors[0] || 'Enregistrement impossible. Verifiez les informations et reessayez.';
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
            this.resetSaleToken();
            this.persistCart();
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

            if (type === 'success') {
                setTimeout(() => this.showToast = false, 5000);
            }
        }
    }
}
</script>
@endsection
