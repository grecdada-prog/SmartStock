@extends('manager.layouts.app')

@section('title', 'Dashboard Gérant')

@section('content')
    <div class="py-2" x-data="{ showCash: false, showMobile: false, showToday: false, showYesterday: false }">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <a href="{{ route('manager.sellers.create') }}" class="group relative rounded-lg bg-white p-4 shadow-sm transition hover:shadow-md focus-within:ring-2 focus-within:ring-rose-500">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-rose-50 text-rose-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Ajouter un vendeur
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">Creer un compte vendeur</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('manager.products.create') }}" class="group relative rounded-lg bg-white p-4 shadow-sm transition hover:shadow-md focus-within:ring-2 focus-within:ring-rose-500">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-blue-50 text-blue-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Ajouter un produit
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">Creer une fiche produit</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('manager.stock.restock') }}" class="group relative rounded-lg bg-white p-4 shadow-sm transition hover:shadow-md focus-within:ring-2 focus-within:ring-rose-500">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-purple-50 text-purple-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Reapprovisionner
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">Gerer les entrees stock</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Paiements mobiles</p>
                            <p class="mt-2 break-words text-2xl font-semibold leading-tight text-gray-900" x-text="showMobile ? '{{ number_format($stats['total_mobile_money_balance'], 0, ',', ' ') }} FCFA' : '******'"></p>
                        </div>
                        <button type="button" @click="showMobile = !showMobile" class="rounded-md p-2 text-gray-400 hover:bg-gray-50 hover:text-gray-700" aria-label="Afficher ou masquer les soldes mobiles">
                            <svg x-show="!showMobile" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                            <svg x-show="showMobile" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-4 text-sm text-gray-600">Orange Money + MTN Momo</p>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Solde Cash</p>
                            <p class="mt-2 break-words text-2xl font-semibold leading-tight text-gray-900" x-text="showCash ? '{{ number_format($stats['total_cash_balance'], 0, ',', ' ') }} FCFA' : '******'"></p>
                        </div>
                        <button type="button" @click="showCash = !showCash; if (showCash) window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }))" class="rounded-md p-2 text-gray-400 hover:bg-gray-50 hover:text-gray-700" aria-label="Afficher ou masquer le solde cash">
                            <svg x-show="!showCash" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                            <svg x-show="showCash" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 max-h-40 overflow-y-auto overflow-x-hidden divide-y divide-gray-100 text-sm">
                        @forelse($sellerFinancials as $row)
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2">
                                <a href="{{ route('manager.sellers.show', $row['seller']) }}" class="min-w-0 flex-1 truncate text-gray-600 hover:text-rose-700">{{ $row['seller']->name }}</a>
                                <div class="flex w-full flex-wrap items-center justify-between gap-2 sm:w-auto sm:justify-end">
                                    <span class="whitespace-nowrap font-medium text-gray-900" x-text="showCash ? '{{ number_format($row['cash_balance'], 0, ',', ' ') }} FCFA' : '******'"></span>
                                    <a href="{{ route('manager.sellers.show', $row['seller']) }}" class="whitespace-nowrap rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-700">Ajouter/Retirer</a>
                                </div>
                            </div>
                        @empty
                            <p class="py-2 text-gray-500">Aucun vendeur.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Recette du jour</p>
                            <p class="mt-2 break-words text-2xl font-semibold leading-tight text-gray-900" x-text="showToday ? '{{ number_format($stats['total_current_day_revenue'], 0, ',', ' ') }} FCFA' : '******'"></p>
                        </div>
                        <button type="button" @click="showToday = !showToday; if (showToday) window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }))" class="rounded-md p-2 text-gray-400 hover:bg-gray-50 hover:text-gray-700" aria-label="Afficher ou masquer la recette du jour">
                            <svg x-show="!showToday" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                            <svg x-show="showToday" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 max-h-40 overflow-y-auto divide-y divide-gray-100 text-sm">
                        @forelse($sellerFinancials as $row)
                            <div class="flex items-center justify-between py-2">
                                <span class="truncate text-gray-600">{{ $row['seller']->name }}</span>
                                <span class="font-medium text-gray-900" x-text="showToday ? '{{ number_format($row['today_revenue'], 0, ',', ' ') }} FCFA' : '******'"></span>
                            </div>
                        @empty
                            <p class="py-2 text-gray-500">Aucun vendeur.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Recette d'hier</p>
                            <p class="mt-2 break-words text-2xl font-semibold leading-tight text-gray-900" x-text="showYesterday ? '{{ number_format($stats['total_yesterday_revenue'], 0, ',', ' ') }} FCFA' : '******'"></p>
                        </div>
                        <button type="button" @click="showYesterday = !showYesterday; if (showYesterday) window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }))" class="rounded-md p-2 text-gray-400 hover:bg-gray-50 hover:text-gray-700" aria-label="Afficher ou masquer la recette d'hier">
                            <svg x-show="!showYesterday" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                            <svg x-show="showYesterday" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 max-h-40 overflow-y-auto divide-y divide-gray-100 text-sm">
                        @forelse($sellerFinancials as $row)
                            <div class="flex items-center justify-between py-2">
                                <span class="truncate text-gray-600">{{ $row['seller']->name }}</span>
                                <span class="font-medium text-gray-900" x-text="showYesterday ? '{{ number_format($row['yesterday_revenue'], 0, ',', ' ') }} FCFA' : '******'"></span>
                            </div>
                        @empty
                            <p class="py-2 text-gray-500">Aucun vendeur.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <h3 class="mb-5 text-lg font-medium text-gray-900">Top Vendeurs du Mois</h3>
                        <div class="flow-root">
                            <ul role="list" class="-my-5 divide-y divide-gray-200">
                                @forelse($topSellers as $seller)
                                    <li class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-rose-100">
                                                <span class="text-sm font-medium text-rose-600">{{ strtoupper(substr($seller->name, 0, 2)) }}</span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-gray-900">{{ $seller->name }}</p>
                                                <p class="text-sm text-gray-500">{{ $seller->sales_count }} ventes</p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">
                                                {{ number_format($seller->sales_sum_total ?? 0, 0, ',', ' ') }} FCFA
                                            </span>
                                        </div>
                                    </li>
                                @empty
                                    <li class="py-8 text-center text-gray-500">Aucune donnee ce mois-ci</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Alertes Stock Faible</h3>
                            <a href="{{ route('manager.stock.low-stock') }}" class="text-sm font-medium text-rose-600 hover:text-rose-500">Voir tout</a>
                        </div>
                        <div class="flow-root">
                            <ul role="list" class="-my-5 divide-y divide-gray-200">
                                @forelse($lowStockProducts as $product)
                                    <li class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-gray-900">{{ $product->name }}</p>
                                                <p class="text-sm text-gray-500">{{ $product->category->name }}</p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                                {{ $product->quantity }} {{ $product->unit }}
                                            </span>
                                        </div>
                                    </li>
                                @empty
                                    <li class="py-8 text-center text-gray-500">Aucune alerte de stock</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
