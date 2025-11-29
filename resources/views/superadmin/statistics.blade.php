<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Statistiques Détaillées') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Sélecteur de période -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Période d'analyse</h3>
                        <div class="flex space-x-2">
                            <button onclick="changePeriod('today')" id="btn-today" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                                Aujourd'hui
                            </button>
                            <button onclick="changePeriod('week')" id="btn-week" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                                Cette semaine
                            </button>
                            <button onclick="changePeriod('month')" id="btn-month" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                                Ce mois
                            </button>
                            <button onclick="changePeriod('year')" id="btn-year" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                                Cette année
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques par période -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                @foreach($statistics as $period => $stats)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" data-period="{{ $period }}" style="{{ $period != 'today' ? 'display:none;' : '' }}">
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-gray-500 mb-4">
                                @if($period == 'today') Aujourd'hui
                                @elseif($period == 'week') Cette semaine
                                @elseif($period == 'month') Ce mois
                                @else Cette année
                                @endif
                            </h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-500">Nombre de ventes</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $stats['sales_count'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Chiffre d'affaires</p>
                                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['revenue'], 0, ',', ' ') }} FCFA</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Vente moyenne</p>
                                    <p class="text-lg font-semibold text-gray-700">{{ number_format($stats['average_sale'], 0, ',', ' ') }} FCFA</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Graphique des ventes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-5">Évolution des Ventes (7 derniers jours)</h3>
                    <canvas id="salesChart" width="400" height="100"></canvas>
                </div>
            </div>

            <!-- Top Vendeurs et Top Produits -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <!-- Top Vendeurs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-5">Top 5 Vendeurs du Mois</h3>
                        <canvas id="sellersChart" width="400" height="300"></canvas>
                    </div>
                </div>

                <!-- Top Produits -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-5">Top 5 Produits Vendus</h3>
                        <canvas id="productsChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Fonction pour changer de période
        function changePeriod(period) {
            // Cacher toutes les cartes
            document.querySelectorAll('[data-period]').forEach(el => {
                el.style.display = 'none';
            });
            
            // Afficher la carte sélectionnée
            document.querySelectorAll(`[data-period="${period}"]`).forEach(el => {
                el.style.display = 'block';
            });

            // Mettre à jour les boutons
            document.querySelectorAll('[id^="btn-"]').forEach(btn => {
                btn.classList.remove('bg-green-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            document.getElementById(`btn-${period}`).classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById(`btn-${period}`).classList.add('bg-green-600', 'text-white');
        }

        // Graphique des ventes
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
                    label: 'Chiffre d\'affaires (FCFA)',
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
                            text: 'CA (FCFA)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        // Graphique Top Vendeurs
        const sellersCtx = document.getElementById('sellersChart').getContext('2d');
        new Chart(sellersCtx, {
            type: 'bar',
            data: {
                labels: @json($topSellers->pluck('name')),
                datasets: [{
                    label: 'CA (FCFA)',
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
                            text: 'Chiffre d\'affaires (FCFA)'
                        }
                    }
                }
            }
        });

        // Graphique Top Produits
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
</x-app-layout>