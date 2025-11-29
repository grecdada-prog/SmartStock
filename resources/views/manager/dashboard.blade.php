<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Gérant') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Statistiques principales -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <!-- Vendeurs actifs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Vendeurs</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_sellers'] }}</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm">
                                <span class="text-green-600 font-medium">{{ $stats['online_sellers'] }}</span>
                                <span class="text-gray-600"> en ligne</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Produits -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Produits</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_products'] }}</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm">
                                <span class="text-red-600 font-medium">{{ $stats['low_stock_products'] }}</span>
                                <span class="text-gray-600"> stock faible</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ventes du jour -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Ventes Aujourd'hui</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">{{ $stats['today_sales'] }}</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm text-gray-600">
                                {{ number_format($stats['today_revenue'], 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CA Total -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">CA Total</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_revenue'], 0, ',', ' ') }}</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm text-gray-600">
                                {{ $stats['total_sales'] }} ventes
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accès Rapide aux Modules -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-5">Accès Rapide aux Modules</h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <!-- Vendeurs -->
                        <a href="{{ route('manager.sellers.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Vendeurs</span>
                            <span class="text-xs text-gray-500 mt-1">{{ $stats['online_sellers'] }} en ligne</span>
                        </a>

                        <!-- Catégories -->
                        <a href="{{ route('manager.categories.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Catégories</span>
                            <span class="text-xs text-gray-500 mt-1">{{ $stats['total_categories'] }} catégories</span>
                        </a>

                        <!-- Produits -->
                        <a href="{{ route('manager.products.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Produits</span>
                            <span class="text-xs text-gray-500 mt-1">{{ $stats['total_products'] }} produits</span>
                        </a>

                        <!-- Stock -->
                        <a href="{{ route('manager.stock.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Gestion Stock</span>
                            <span class="text-xs text-red-500 mt-1">{{ $stats['low_stock_products'] }} alertes</span>
                        </a>

                        <!-- Stock faible -->
                        <a href="{{ route('manager.stock.low-stock') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-red-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Stock Faible</span>
                            <span class="text-xs text-gray-500 mt-1">Alertes</span>
                        </a>

                        <!-- Mouvements -->
                        <a href="{{ route('manager.stock.movements') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-yellow-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Mouvements</span>
                            <span class="text-xs text-gray-500 mt-1">Historique</span>
                        </a>

                        <!-- Rapports Ventes -->
                        <a href="{{ route('manager.reports.sales') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-pink-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Rapport Ventes</span>
                            <span class="text-xs text-gray-500 mt-1">Analyses</span>
                        </a>

                        <!-- Ventes en ligne -->
                        <a href="{{ route('manager.sellers.online') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-teal-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Vendeurs Actifs</span>
                            <span class="text-xs text-green-500 mt-1">Temps réel</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Top vendeurs et alertes stock -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 mb-8">
                <!-- Top vendeurs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-5">Top Vendeurs du Mois</h3>
                        <div class="flow-root">
                            <ul role="list" class="-my-5 divide-y divide-gray-200">
                                @forelse($topSellers as $seller)
                                    <li class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                    <span class="text-green-600 font-medium text-sm">{{ strtoupper(substr($seller->name, 0, 2)) }}</span>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $seller->name }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ $seller->sales_count }} ventes
                                                </p>
                                            </div>
                                            <div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ number_format($seller->sales_sum_total ?? 0, 0, ',', ' ') }} FCFA
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-center text-gray-500 py-8">Aucune donnée ce mois-ci</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Produits en stock faible -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-lg font-medium text-gray-900">Alertes Stock Faible</h3>
                            <a href="{{ route('manager.stock.low-stock') }}" class="text-sm font-medium text-green-600 hover:text-green-500">
                                Voir tout
                            </a>
                        </div>
                        <div class="flow-root">
                            <ul role="list" class="-my-5 divide-y divide-gray-200">
                                @forelse($lowStockProducts as $product)
                                    <li class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $product->name }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ $product->category->name }}
                                                </p>
                                            </div>
                                            <div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    {{ $product->quantity }} {{ $product->unit }}
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-center text-gray-500 py-8">Aucune alerte de stock</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <a href="{{ route('manager.sellers.create') }}" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-green-500 rounded-lg shadow-sm hover:shadow-md transition">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-green-50 text-green-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            Ajouter un vendeur
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Créer un nouveau compte vendeur
                        </p>
                    </div>
                </a>

                <a href="{{ route('manager.products.create') }}" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-green-500 rounded-lg shadow-sm hover:shadow-md transition">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-blue-50 text-blue-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            Ajouter un produit
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Créer une nouvelle fiche produit
                        </p>
                    </div>
                </a>

                <a href="{{ route('manager.stock.restock') }}" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-green-500 rounded-lg shadow-sm hover:shadow-md transition">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-purple-50 text-purple-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            Réapprovisionner
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Gérer les entrées de stock
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>