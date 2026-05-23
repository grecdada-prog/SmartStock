<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard vendeur
        </h2>
    </x-slot>

    <div class="py-4" x-data="{ showToday: false, showYesterday: false, showCash: false, showMobile: false, showCloseModal: false, closingCash: false, openingCash: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="smartstore-sticky-zone px-4 sm:px-0">
                <div class="smartstore-sticky-inner">
            <div class="smartstore-sticky-cards grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="money-amount-row">
                            <p class="min-w-0 flex-1 text-sm font-medium text-gray-500">Recette du jour</p>
                            <x-money-eye-button state="showToday" label="la recette du jour" refresh-on-show />
                        </div>
                        <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="showToday ? '{{ number_format($stats['today_revenue'], 0, ',', ' ') }} FCFA' : '******'"></p>
                        <p class="mt-2 text-sm text-gray-500">{{ number_format($stats['today_sales']) }} vente(s)</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="money-amount-row">
                            <p class="min-w-0 flex-1 text-sm font-medium text-gray-500">Recette d'hier</p>
                            <x-money-eye-button state="showYesterday" label="la recette d'hier" refresh-on-show />
                        </div>
                        <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="showYesterday ? '{{ number_format($stats['yesterday_revenue'], 0, ',', ' ') }} FCFA' : '******'"></p>
                        <p class="mt-2 text-sm text-gray-500">{{ number_format($stats['yesterday_sales']) }} vente(s)</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="money-amount-row">
                            <p class="min-w-0 flex-1 text-sm font-medium text-gray-500">Solde Cash</p>
                            <x-money-eye-button state="showCash" label="le solde cash" refresh-on-show />
                        </div>
                        <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="showCash ? '{{ number_format($stats['cash_balance'], 0, ',', ' ') }} FCFA' : '******'"></p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="money-amount-row">
                            <p class="min-w-0 flex-1 text-sm font-medium text-gray-500">Paiements mobiles</p>
                            <x-money-eye-button state="showMobile" label="les soldes mobiles" />
                        </div>
                        <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="showMobile ? '{{ number_format($stats['mobile_money_balance'], 0, ',', ' ') }} FCFA' : '******'"></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('seller.pos.index') }}" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-rose-500 rounded-lg shadow-sm hover:shadow-md transition">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            Nouvelle Vente
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Acceder au point de vente
                        </p>
                    </div>
                </a>
            </div>
                </div>
            </div>

            <div class="mt-5 rounded-lg border border-red-200 bg-white p-5 shadow-sm">
                @if(!$stats['cash_register_is_open'])
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Caisse non ouverte</h3>
                            <p class="mt-1 text-sm text-gray-500">Les ventes sont bloquees jusqu'a votre ouverture manuelle de la caisse.</p>
                        </div>
                        <form x-ref="openForm" method="POST" action="{{ route('seller.dashboard.open-cash-register') }}">
                            @csrf
                            <button
                                type="button"
                                @click="openingCash = true; setTimeout(() => $refs.openForm.submit(), 1200)"
                                :disabled="openingCash"
                                class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-rose-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 disabled:cursor-wait disabled:opacity-70 lg:w-auto"
                            >
                                <svg x-show="openingCash" class="-ml-1 mr-2 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="openingCash ? 'Ouverture...' : 'Ouvrir la caisse'"></span>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-normal text-green-700">Caisse ouverte</p>
                            <h3 class="text-base font-semibold text-red-900">Fermeture de caisse</h3>
                        </div>
                        <button
                            type="button"
                            @click="showCloseModal = true"
                            class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-red-700 lg:w-auto"
                        >
                            Fermer la caisse
                        </button>
                    </div>

                    <form x-ref="closeForm" method="POST" action="{{ route('seller.dashboard.close-cash-register') }}" class="hidden">
                        @csrf
                    </form>
                @endif
            </div>
        </div>

        <div
            x-show="showCloseModal"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 px-4"
            style="display: none;"
        >
            <div @click.away="!closingCash && (showCloseModal = false)" class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
                <div class="border-b border-rose-100 bg-rose-50 px-6 py-5">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 9v3m0 4h.01M10.29 3.86 1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Cloturer la caisse</h3>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="showCloseModal = false"
                        :disabled="closingCash"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        @click="closingCash = true; $refs.closeForm.submit()"
                        :disabled="closingCash"
                        class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-wait disabled:opacity-80"
                    >
                        <svg x-show="closingCash" class="-ml-1 mr-2 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="closingCash ? 'Cloture...' : 'Cloturer'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
