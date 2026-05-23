@extends('superadmin.layouts.app')

@section('title', 'Dashboard Super Administrateur')

@section('content')
<div class="space-y-8">
    <div class="smartstore-sticky-zone">
        <div class="smartstore-sticky-inner">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Dashboard</h1>
        </div>
        <a href="{{ route('superadmin.statistics') }}"
           class="inline-flex w-fit items-center justify-center rounded-md border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-50">
            Statistiques
        </a>
    </div>

    <section class="smartstore-sticky-cards grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <a href="{{ route('superadmin.users.index') }}" class="rounded-lg border border-gray-100 bg-white p-5 shadow-sm transition hover:border-rose-200 hover:shadow-md">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Utilisateurs</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['total_users'] }}</p>
                </div>
                <span class="rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ $stats['active_users'] }} actifs</span>
            </div>
        </a>

        <a href="{{ route('superadmin.products') }}" class="rounded-lg border border-gray-100 bg-white p-5 shadow-sm transition hover:border-rose-200 hover:shadow-md">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Produits</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['total_products'] }}</p>
                </div>
                <span class="rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">{{ $stats['low_stock_products'] }} stock faible</span>
            </div>
        </a>

        <x-money-stat-card
            class="border border-gray-100 shadow-sm"
            title="Recette du jour"
            :amount="number_format($stats['total_current_day_revenue'], 0, ',', ' ') . ' FCFA'"
            label="la recette du jour"
            :footer="number_format($stats['today_sales']) . ' vente(s)'"
        />

        <div class="money-stat-card rounded-lg border border-gray-100 bg-white p-5 shadow-sm" x-data="{ showCashCard: false }">
            <div class="money-amount-row">
                <p class="min-w-0 flex-1 pr-2 text-sm font-medium text-gray-500">Solde Cash</p>
                <x-money-eye-button state="showCashCard" label="le solde cash et la recette d'hier" refresh-on-show />
            </div>
            <p class="mt-2 text-2xl font-semibold text-gray-900">
                <x-money-value
                    state="showCashCard"
                    :amount="number_format($stats['total_cash_balance'], 0, ',', ' ') . ' FCFA'" />
            </p>
            <div class="mt-2 flex items-center gap-1 text-sm text-gray-500">
                <x-money-value
                    state="showCashCard"
                    class="text-sm font-semibold text-rose-600"
                    :amount="number_format($stats['total_yesterday_revenue'], 0, ',', ' ') . ' FCFA'" />
                <span>hier</span>
            </div>
        </div>

        <x-money-stat-card
            class="border border-gray-100 shadow-sm"
            title="Paiements mobiles"
            :amount="number_format($stats['total_mobile_money_balance'], 0, ',', ' ') . ' FCFA'"
            label="le solde des paiements mobiles"
        />
    </section>
        </div>
    </div>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Utilisateurs en ligne</h2>
                <a href="{{ route('superadmin.sessions.active') }}" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Sessions</a>
            </div>
            <div class="mt-5 grid grid-cols-3 gap-3">
                <a href="{{ route('superadmin.managers.index') }}" class="rounded-md bg-rose-50 p-4">
                    <p class="text-xs font-medium text-gray-500">Gerants</p>
                    <p class="mt-1 text-2xl font-semibold text-rose-700">{{ $onlineUsers['managers'] }}</p>
                </a>
                <a href="{{ route('superadmin.sellers.index') }}" class="rounded-md bg-blue-50 p-4">
                    <p class="text-xs font-medium text-gray-500">Vendeurs</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-700">{{ $onlineUsers['sellers'] }}</p>
                </a>
                <div class="rounded-md bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-500">Total</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $onlineUsers['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-100 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Actions rapides</h2>
                </div>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-4">
                <a href="{{ route('superadmin.users.create') }}" class="rounded-md border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">Nouvel utilisateur</a>
                <a href="{{ route('superadmin.managers.create') }}" class="rounded-md border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">Nouveau gerant</a>
                <a href="{{ route('superadmin.anomalies') }}" class="rounded-md border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">Anomalies</a>
                <a href="{{ route('superadmin.activity-logs') }}" class="rounded-md border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">Logs</a>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-100 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-gray-100 p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Supervision des gérants</h2>
                        <p class="mt-1 text-sm text-gray-500">Equipes, recettes et etat des caisses.</p>
                    </div>
                    <a href="{{ route('superadmin.managers.index') }}" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Gerer</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Gerant</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Equipe</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Recette</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Soldes</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Etat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($managerSummaries as $summary)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="font-semibold text-gray-900">{{ $summary['manager']->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $summary['manager']->email }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                    {{ $summary['active_sellers'] }}/{{ $summary['sellers_count'] }} actifs
                                    <p class="text-xs text-gray-500">{{ $summary['online_sellers'] }} en ligne</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="font-semibold text-gray-900">{{ number_format($summary['today_revenue'], 0, ',', ' ') }} FCFA</p>
                                    <p class="text-xs text-gray-500">Hier: {{ number_format($summary['yesterday_revenue'], 0, ',', ' ') }} FCFA</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="font-semibold text-gray-900">Cash: {{ number_format($summary['cash_balance'], 0, ',', ' ') }} FCFA</p>
                                    <p class="text-xs text-gray-500">Mobile: {{ number_format($summary['mobile_money_balance'], 0, ',', ' ') }} FCFA</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @if($summary['pending_closures'] > 0)
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">{{ $summary['pending_closures'] }} caisse(s) fermee(s)</span>
                                        @endif
                                        @if($summary['low_stock_products'] > 0)
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">{{ $summary['low_stock_products'] }} stock faible</span>
                                        @endif
                                        @if($summary['pending_closures'] === 0 && $summary['low_stock_products'] === 0)
                                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">OK</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-500">Aucun gerant disponible</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Alertes superadmin</h2>
                    <p class="mt-1 text-sm text-gray-500">Priorites a traiter.</p>
                </div>
                <a href="{{ route('superadmin.anomalies') }}" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Voir</a>
            </div>
            <div class="mt-5 space-y-3">
                @forelse($oversightAlerts as $alert)
                    @php
                        $palette = match($alert['severity']) {
                            'danger' => 'border-red-200 bg-red-50 text-red-800',
                            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
                            default => 'border-blue-200 bg-blue-50 text-blue-800',
                        };
                    @endphp
                    <div class="rounded-md border p-3 {{ $palette }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ $alert['title'] }}</p>
                                <p class="mt-1 text-sm">{{ $alert['message'] }}</p>
                            </div>
                            <a href="{{ $alert['route'] }}" class="shrink-0 text-xs font-semibold underline">{{ $alert['cta'] }}</a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                        Aucune alerte critique.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <h2 class="text-base font-semibold text-gray-900">Fermetures de caisse</h2>
                <p class="mt-1 text-sm text-gray-500">Dernieres operations de caisse.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Vendeur</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Journee</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($cashRegisterClosures as $closure)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-900">{{ $closure->seller->name ?? 'Vendeur supprime' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $closure->business_date->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if($closure->opened_at)
                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">Rouverte</span>
                                    @else
                                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">Fermee</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center text-gray-500">Aucune fermeture enregistree</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Activites recentes</h2>
                <a href="{{ route('superadmin.activity-logs') }}" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Voir tout</a>
            </div>
            <div class="mt-5 flow-root">
                <ul role="list" class="-mb-6">
                    @forelse($recentActivities as $activity)
                        <li>
                            <div class="relative pb-6">
                                @if(!$loop->last)
                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-xs font-semibold text-rose-700 ring-8 ring-white">
                                        {{ strtoupper(substr($activity->user->name ?? 'S', 0, 1)) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-600">
                                            <span class="font-semibold text-gray-900">{{ $activity->user->name ?? 'Systeme' }}</span>
                                            {{ $activity->description }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="py-8 text-center text-sm text-gray-500">Aucune activite recente</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>
</div>
@endsection
