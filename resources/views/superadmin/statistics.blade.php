@extends('superadmin.layouts.app')

@section('title', 'Statistiques')

@section('content')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Statistiques</h1>
                    <p class="mt-1 text-sm text-gray-600">Vue globale des ventes et des performances.</p>
                </div>
                <a href="{{ route('superadmin.dashboard') }}" class="inline-flex w-fit items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Retour dashboard
                </a>
            </div>

            <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Periode d'analyse</h3>
                        <div class="flex space-x-2">
                            <button onclick="changePeriod('today')" id="btn-today" class="rounded-md bg-rose-600 px-4 py-2 text-white transition hover:bg-rose-700">
                                Aujourd'hui
                            </button>
                            <button onclick="changePeriod('week')" id="btn-week" class="rounded-md bg-gray-200 px-4 py-2 text-gray-700 transition hover:bg-gray-300">
                                Cette semaine
                            </button>
                            <button onclick="changePeriod('month')" id="btn-month" class="rounded-md bg-gray-200 px-4 py-2 text-gray-700 transition hover:bg-gray-300">
                                Ce mois
                            </button>
                            <button onclick="changePeriod('year')" id="btn-year" class="rounded-md bg-gray-200 px-4 py-2 text-gray-700 transition hover:bg-gray-300">
                                Cette annee
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($statistics as $period => $stats)
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg" data-period="{{ $period }}" style="{{ $period != 'today' ? 'display:none;' : '' }}">
                        <div class="p-6">
                            <h4 class="mb-4 text-sm font-medium text-gray-500">
                                @if($period == 'today') Aujourd'hui
                                @elseif($period == 'week') Cette semaine
                                @elseif($period == 'month') Ce mois
                                @else Cette annee
                                @endif
                            </h4>

                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-500">Nombre de ventes</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $stats['sales_count'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Recette</p>
                                    <p class="text-2xl font-bold text-rose-600">{{ number_format($stats['revenue'], 0, ',', ' ') }} FCFA</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Panier moyen</p>
                                    <p class="text-lg font-semibold text-gray-700">{{ number_format($stats['average_sale'], 0, ',', ' ') }} FCFA</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mb-8 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="mb-5 text-lg font-medium text-gray-900">Evolution des ventes (7 derniers jours)</h3>
                    <canvas id="salesChart" width="400" height="100"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-5 text-lg font-medium text-gray-900">Top 5 vendeurs du mois</h3>
                        <canvas id="sellersChart" width="400" height="300"></canvas>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-5 text-lg font-medium text-gray-900">Top 5 produits vendus</h3>
                        <canvas id="productsChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        function changePeriod(period) {
            document.querySelectorAll('[data-period]').forEach((el) => {
                el.style.display = 'none';
            });

            document.querySelectorAll(`[data-period="${period}"]`).forEach((el) => {
                el.style.display = 'block';
            });

            document.querySelectorAll('[id^="btn-"]').forEach((btn) => {
                btn.classList.remove('bg-rose-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });

            document.getElementById(`btn-${period}`).classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById(`btn-${period}`).classList.add('bg-rose-600', 'text-white');
        }

        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: @json($salesChart->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))),
                datasets: [{
                    label: 'Ventes',
                    data: @json($salesChart->pluck('count')),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Recette (FCFA)',
                    data: @json($salesChart->pluck('revenue')),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Nombre de ventes'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Recette (FCFA)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        const sellersCtx = document.getElementById('sellersChart').getContext('2d');
        new Chart(sellersCtx, {
            type: 'bar',
            data: {
                labels: @json($topSellers->pluck('name')),
                datasets: [{
                    label: 'Recette cumulée (FCFA)',
                    data: @json($topSellers->pluck('sales_sum_total')),
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(251, 146, 60)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Recette cumulée (FCFA)'
                        }
                    }
                }
            }
        });

        const productsCtx = document.getElementById('productsChart').getContext('2d');
        new Chart(productsCtx, {
            type: 'doughnut',
            data: {
                labels: @json($topProducts->pluck('name')),
                datasets: [{
                    data: @json($topProducts->pluck('total_sold')),
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(251, 146, 60)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
@endsection
