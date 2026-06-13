@extends('seller.layouts.app')

@section('title', 'Mes Statistiques')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Mes Statistiques</h1>
            <p class="mt-2 text-sm text-gray-700">Analyse détaillée de vos performances</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="smartstore-sticky-cards grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Aujourd'hui</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $stats['today_sales'] }}</div>
                                <div class="ml-2 text-sm text-gray-500">vente(s)</div>
                            </dd>
                            <dd class="mt-1 text-sm text-rose-600 font-semibold">
                                {{ number_format($stats['today_revenue'], 0, ',', ' ') }} FCFA
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ce mois</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $stats['month_sales'] }}</div>
                                <div class="ml-2 text-sm text-gray-500">vente(s)</div>
                            </dd>
                            <dd class="mt-1 text-sm text-rose-600 font-semibold">
                                {{ number_format($stats['month_revenue'], 0, ',', ' ') }} FCFA
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Ventes</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_sales'] }}</div>
                                <div class="ml-2 text-sm text-gray-500">vente(s)</div>
                            </dd>
                            <dd class="mt-1 text-sm text-rose-600 font-semibold">
                                {{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Produits Disponibles</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $stats['available_products'] }}</div>
                            </dd>
                            <dd class="mt-1 text-sm text-gray-600">
                                @if($stats['low_stock_products'] > 0)
                                    <span class="text-orange-600">{{ $stats['low_stock_products'] }} en stock faible</span>
                                @else
                                    Tous en stock
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>

    <!-- Graphique des ventes -->
    <div class="mt-6 bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Évolution des ventes (7 derniers jours)</h2>
        <div class="h-64">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Statistiques supplémentaires -->
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Répartition par méthode de paiement</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Espèces</span>
                    <span class="text-sm font-medium">{{ \App\Models\Sale::where('seller_id', auth()->id())->where('payment_method', 'cash')->count() }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Caisse MOMO/OM</span>
                    <span class="text-sm font-medium">{{ \App\Models\Sale::where('seller_id', auth()->id())->whereIn('payment_method', ['card', 'mobile_money'])->count() }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Performance mensuelle</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Moyenne par jour</span>
                    <span class="text-sm font-medium">{{ number_format($stats['month_revenue'] / max(now()->day, 1), 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Objectif mensuel</span>
                    <span class="text-sm font-medium">À définir</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Taux de conversion</span>
                    <span class="text-sm font-medium">N/A</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesData = @json($salesChart);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.map(item => item.date),
            datasets: [{
                label: 'Ventes (FCFA)',
                data: salesData.map(item => item.sales),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' FCFA';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
