@extends('superadmin.layouts.app')

@section('title', 'Dashboard Super Administrateur')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">
                Dashboard Super Administrateur
            </h1>

            <!-- Statistiques principales -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <!-- Total utilisateurs -->
                <a href="{{ route('superadmin.users.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-rose-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Utilisateurs</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_users'] }}</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm">
                                <span class="text-rose-600 font-medium">{{ $stats['active_users'] }}</span>
                                <span class="text-gray-600"> actifs</span>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Total produits -->
                <a href="{{ route('superadmin.products') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Produits</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_products'] }}</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm">
                                <span class="text-red-600 font-medium">{{ $stats['low_stock_products'] }}</span>
                                <span class="text-gray-600"> en stock faible</span>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Recette du jour -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-rose-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Recette du jour</dt>
                                    <dd>
                                        <x-money-toggle
                                            :amount="number_format($stats['total_current_day_revenue'], 0, ',', ' ') . ' FCFA'"
                                            label="la recette du jour" />
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm">
                                <span class="text-rose-600 font-medium">{{ $stats['today_sales'] }}</span>
                                <span class="text-gray-600"> vente(s) aujourd'hui</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Solde cash -->
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Solde Cash</dt>
                                    <dd>
                                        <x-money-toggle
                                            :amount="number_format($stats['total_cash_balance'], 0, ',', ' ') . ' FCFA'"
                                            label="le solde cash" />
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="text-sm">
                                <x-money-toggle
                                    :amount="number_format($stats['total_yesterday_revenue'], 0, ',', ' ') . ' FCFA'"
                                    label="la recette d'hier"
                                    value-class="text-sm font-medium text-rose-600" />
                                <span class="text-gray-600"> recette d'hier</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Utilisateurs en ligne -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-5">Utilisateurs en Ligne</h3>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <a href="{{ route('superadmin.managers.index') }}" class="bg-rose-50 overflow-hidden rounded-lg px-4 py-5 border border-rose-200 hover:bg-rose-100 transition">
                            <dt class="text-sm font-medium text-gray-500 truncate">Gérants</dt>
                            <dd class="mt-1 text-3xl font-semibold text-rose-600">{{ $onlineUsers['managers'] }}</dd>
                        </a>
                        <a href="{{ route('superadmin.sellers.index') }}" class="bg-blue-50 overflow-hidden rounded-lg px-4 py-5 border border-blue-200 hover:bg-blue-100 transition">
                            <dt class="text-sm font-medium text-gray-500 truncate">Vendeurs</dt>
                            <dd class="mt-1 text-3xl font-semibold text-blue-600">{{ $onlineUsers['sellers'] }}</dd>
                        </a>
                        <div class="bg-purple-50 overflow-hidden rounded-lg px-4 py-5 border border-purple-200">
                            <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
                            <dd class="mt-1 text-3xl font-semibold text-purple-600">{{ $onlineUsers['total'] }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accès Rapide aux Modules -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-5">Accès Rapide aux Modules</h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <!-- Gestion Utilisateurs -->
                        <a href="{{ route('superadmin.users.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-rose-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Utilisateurs</span>
                        </a>

                        <!-- Gérants -->
                        <a href="{{ route('superadmin.managers.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Gérants</span>
                        </a>

                        <!-- Vendeurs -->
                        <a href="{{ route('superadmin.sellers.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Vendeurs</span>
                        </a>

                        <!-- Produits -->
                        <a href="{{ route('superadmin.products') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Produits</span>
                        </a>

                        <!-- Ventes -->
                        <a href="{{ route('superadmin.sales') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-pink-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Ventes</span>
                        </a>

                        <!-- Statistiques -->
                        <a href="{{ route('superadmin.statistics') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-yellow-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Statistiques</span>
                        </a>

                        <!-- Anomalies -->
                        <a href="{{ route('superadmin.anomalies') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-amber-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-8.2 14.2A1 1 0 003 19h18a1 1 0 00.91-1.44l-8.2-14.2a1 1 0 00-1.72 0z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Anomalies</span>
                        </a>

                        <!-- Sessions Actives -->
                        <a href="{{ route('superadmin.sessions.active') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-rose-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Sessions Actives</span>
                        </a>

                        <!-- Logs d'activité -->
                        <a href="{{ route('superadmin.activity-logs') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-red-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Logs</span>
                        </a>

                        <!-- Mon Profil -->
                        <a href="{{ route('account.profile.show') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="h-8 w-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Mon Profil</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Fermetures de caisse -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Fermetures de caisse</h3>
                            <p class="mt-1 text-sm text-gray-500">Dernières fermetures et réouvertures des caisses vendeurs</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">
                            Supervision globale
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Vendeur</th>
                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Journée</th>
                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Fermeture</th>
                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Reouverture</th>
                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($cashRegisterClosures as $closure)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $closure->seller->name ?? 'Vendeur supprime' }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $closure->business_date->format('d/m/Y') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $closure->closed_at->format('d/m/Y H:i') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                            {{ $closure->opened_at ? $closure->opened_at->format('d/m/Y H:i') : 'En attente' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            @if($closure->opened_at)
                                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">Rouverte</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">Fermée</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucune fermeture de caisse enregistrée</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Supervision globale -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-8">
                <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-5 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Supervision des gérants</h3>
                                <p class="mt-1 text-sm text-gray-500">Vue consolidée des équipes, des recettes et des caisses</p>
                            </div>
                            <a href="{{ route('superadmin.managers.index') }}" class="text-sm font-medium text-rose-600 hover:text-rose-500">
                                Gerer les gérants
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Gérant</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Equipe</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Recette du jour</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Solde Cash</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Alertes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse($managerSummaries as $summary)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <div class="font-medium text-gray-900">{{ $summary['manager']->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $summary['manager']->email }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                                {{ $summary['active_sellers'] }}/{{ $summary['sellers_count'] }} vendeur(s) actifs
                                                <div class="text-xs text-gray-500">{{ $summary['online_sellers'] }} en ligne</div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <div class="font-semibold text-gray-900">{{ number_format($summary['today_revenue'], 0, ',', ' ') }} FCFA</div>
                                                <div class="text-xs text-gray-500">Hier: {{ number_format($summary['yesterday_revenue'], 0, ',', ' ') }} FCFA</div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <div class="font-semibold text-gray-900">{{ number_format($summary['cash_balance'], 0, ',', ' ') }} FCFA</div>
                                                <div class="text-xs text-gray-500">
                                                    Derniere vente:
                                                    {{ $summary['last_sale_at'] ? $summary['last_sale_at']->format('d/m H:i') : 'Aucune' }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap gap-2">
                                                    @if($summary['pending_closures'] > 0)
                                                        <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                                            {{ $summary['pending_closures'] }} caisse(s) fermée(s)
                                                        </span>
                                                    @endif
                                                    @if($summary['low_stock_products'] > 0)
                                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                                            {{ $summary['low_stock_products'] }} stock faible
                                                        </span>
                                                    @endif
                                                    @if($summary['pending_closures'] === 0 && $summary['low_stock_products'] === 0)
                                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                                                            Rien a signaler
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucun gérant disponible</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-5">
                            <h3 class="text-lg font-medium text-gray-900">Alertes superadmin</h3>
                            <p class="mt-1 text-sm text-gray-500">Points de controle prioritaires a traiter</p>
                        </div>

                        <div class="space-y-3">
                            @forelse($oversightAlerts as $alert)
                                @php
                                    $palette = match($alert['severity']) {
                                        'danger' => 'border-red-200 bg-red-50 text-red-800',
                                        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
                                        default => 'border-blue-200 bg-blue-50 text-blue-800',
                                    };
                                @endphp
                                <div class="rounded-lg border p-4 {{ $palette }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold">{{ $alert['title'] }}</p>
                                            <p class="mt-1 text-sm">{{ $alert['message'] }}</p>
                                        </div>
                                        <a href="{{ $alert['route'] }}" class="shrink-0 text-xs font-semibold underline">
                                            {{ $alert['cta'] }}
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                                    Aucune alerte critique pour le moment.
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-6 rounded-lg bg-gray-50 p-4">
                            <h4 class="text-sm font-semibold text-gray-900">Pouvoirs rapides</h4>
                            <div class="mt-3 space-y-2 text-sm text-gray-600">
                                <div class="flex items-center justify-between">
                                    <span>Forcer une deconnexion</span>
                                    <a href="{{ route('superadmin.sessions.active') }}" class="font-medium text-rose-600 hover:text-rose-500">Sessions</a>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Auditer une action ou une vente</span>
                                    <a href="{{ route('superadmin.activity-logs') }}" class="font-medium text-rose-600 hover:text-rose-500">Logs</a>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Traiter les anomalies en cours</span>
                                    <a href="{{ route('superadmin.anomalies') }}" class="font-medium text-rose-600 hover:text-rose-500">Anomalies</a>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Reprendre la main sur les comptes</span>
                                    <a href="{{ route('superadmin.users.index') }}" class="font-medium text-rose-600 hover:text-rose-500">Utilisateurs</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activités récentes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-medium text-gray-900">Activités Récentes</h3>
                        <a href="{{ route('superadmin.activity-logs') }}" class="text-sm font-medium text-rose-600 hover:text-rose-500">
                            Voir tout
                        </a>
                    </div>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @forelse($recentActivities as $activity)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-rose-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        <span class="font-medium text-gray-900">{{ $activity->user->name ?? 'Système' }}</span>
                                                        {{ $activity->description }}
                                                    </p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    <time datetime="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="text-center text-gray-500 py-8">Aucune activité récente</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
                <a href="{{ route('superadmin.users.create') }}" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-rose-500 rounded-lg shadow-sm hover:shadow-md transition">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-rose-50 text-rose-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            Créer un utilisateur
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Ajouter un nouveau super admin, gérant ou vendeur
                        </p>
                    </div>
                </a>

                <a href="{{ route('superadmin.managers.create') }}" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-rose-500 rounded-lg shadow-sm hover:shadow-md transition">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-blue-50 text-blue-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            Créer un gérant
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Ajouter un nouveau gérant au système
                        </p>
                    </div>
                </a>

                <a href="{{ route('superadmin.activity-logs') }}" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-rose-500 rounded-lg shadow-sm hover:shadow-md transition">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-purple-50 text-purple-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            Voir les logs
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Consulter l'historique des activités
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>
@endsection
