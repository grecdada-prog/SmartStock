<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard vendeur
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ showToday: false, showYesterday: false, showCash: false, showCloseModal: false, closingCash: false, openingCash: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Recette du jour</p>
                                <p class="mt-1 text-xs text-gray-500">Recette totale encaissee aujourd'hui</p>
                            </div>
                            <button type="button" @click="showToday = !showToday; if (showToday) window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }))" class="text-gray-400 hover:text-gray-700" aria-label="Afficher ou masquer la recette du jour">
                                <svg x-show="!showToday" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3m3-2A9.8 9.8 0 0112 5c5 0 9 4 10 7a11.7 11.7 0 01-4.1 5.1M3 3l18 18" />
                                </svg>
                                <svg x-show="showToday" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                            </button>
                        </div>
                        <div class="mt-6">
                            <p class="text-3xl font-semibold text-gray-900" x-text="showToday ? '{{ number_format($stats['today_revenue'], 0, ',', ' ') }} FCFA' : '******'"></p>
                            <p class="mt-2 text-sm text-gray-500">{{ $stats['today_cash_sales'] }} vente(s)</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Recette d'hier</p>
                                <p class="mt-1 text-xs text-gray-500">Recette totale encaissee hier</p>
                            </div>
                            <button type="button" @click="showYesterday = !showYesterday; if (showYesterday) window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }))" class="text-gray-400 hover:text-gray-700" aria-label="Afficher ou masquer la recette d'hier">
                                <svg x-show="!showYesterday" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3m3-2A9.8 9.8 0 0112 5c5 0 9 4 10 7a11.7 11.7 0 01-4.1 5.1M3 3l18 18" />
                                </svg>
                                <svg x-show="showYesterday" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                            </button>
                        </div>
                        <div class="mt-6">
                            <p class="text-3xl font-semibold text-gray-900" x-text="showYesterday ? '{{ number_format($stats['yesterday_revenue'], 0, ',', ' ') }} FCFA' : '******'"></p>
                            <p class="mt-2 text-sm text-gray-500">
                                @if($stats['yesterday_cash_sales'] === null)
                                    Recette cloturee
                                @else
                                    {{ $stats['yesterday_cash_sales'] }} vente(s)
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Solde Cash</p>
                                <p class="mt-1 text-xs text-gray-500">Caisse cumulee apres cloture, ajustee par le gerant</p>
                            </div>
                            <button type="button" @click="showCash = !showCash; if (showCash) window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }))" class="text-gray-400 hover:text-gray-700" aria-label="Afficher ou masquer le solde cash">
                                <svg x-show="!showCash" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3m3-2A9.8 9.8 0 0112 5c5 0 9 4 10 7a11.7 11.7 0 01-4.1 5.1M3 3l18 18" />
                                </svg>
                                <svg x-show="showCash" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                                </svg>
                            </button>
                        </div>
                        <div class="mt-6">
                            <p class="text-3xl font-semibold text-gray-900" x-text="showCash ? '{{ number_format($stats['cash_balance'], 0, ',', ' ') }} FCFA' : '******'"></p>
                            <p class="mt-2 text-sm text-gray-500">
                                @if($stats['cash_register_closed_today'])
                                    Journee de vente terminee. Caisse fermee depuis le {{ $stats['cash_register_closed_at']->format('d/m/Y') }} a {{ $stats['cash_register_closed_at']->format('H:i') }}
                                @elseif($stats['cash_register_opened_at'])
                                    Caisse rouverte le {{ $stats['cash_register_opened_at']->format('d/m/Y') }} a {{ $stats['cash_register_opened_at']->format('H:i') }}
                                @else
                                    Caisse ouverte aujourd'hui
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('seller.pos.index') }}" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-rose-500 rounded-lg shadow-sm hover:shadow-md transition">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-rose-50 text-rose-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
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

            <div class="mt-5 rounded-lg border border-red-200 bg-white p-5 shadow-sm">
                @if($stats['cash_register_closed_today'])
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Caisse fermee</h3>
                            <p class="mt-1 text-sm text-gray-500">La recette du jour est transferee dans le Solde Cash. Les ventes sont bloquees jusqu'a l'ouverture.</p>
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
                            <h3 class="text-base font-semibold text-red-900">Fermeture de caisse</h3>
                            <p class="mt-1 text-sm text-gray-600">Action importante : elle termine la journee de vente, transfere la recette du jour dans le Solde Cash et remet la recette du jour a zero.</p>
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
            <div @click.away="!closingCash && (showCloseModal = false)" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900">Confirmer la fermeture</h3>
                <p class="mt-3 text-sm text-gray-600">
                    Cette action ferme la journee de vente. La recette du jour sera transferee dans le Solde Cash, puis la recette du jour sera remise a zero.
                </p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
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
                        @click="closingCash = true; setTimeout(() => $refs.closeForm.submit(), 4000)"
                        :disabled="closingCash"
                        class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-wait disabled:opacity-80"
                    >
                        <svg x-show="closingCash" class="-ml-1 mr-2 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="closingCash ? 'Fermeture en cours...' : 'Confirmer la fermeture'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
