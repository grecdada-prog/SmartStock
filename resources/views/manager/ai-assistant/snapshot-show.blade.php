@extends('manager.layouts.app')

@section('title', 'Detail analyse IA')

@section('content')
    @php
        $severityClasses = [
            'critical' => 'bg-red-50 text-red-700 ring-red-200',
            'high' => 'bg-orange-50 text-orange-700 ring-orange-200',
            'medium' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'stable' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
    @endphp

    <div class="py-2">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-rose-600">Analyse IA historisee</p>
                        <h1 class="mt-1 text-2xl font-bold text-gray-950">{{ $snapshot->generated_at->format('d/m/Y H:i') }}</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">{{ $snapshot->narrative }}</p>
                    </div>
                    <a href="{{ route('manager.ai-assistant.snapshots.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                        Retour historique
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Ruptures</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ $snapshot->kpis['critical_stock'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Reappro</p>
                    <p class="mt-2 text-3xl font-bold text-gray-950">{{ $snapshot->kpis['restock_recommendations'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Peremption</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">{{ $snapshot->kpis['expiry_alerts'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Anomalies</p>
                    <p class="mt-2 text-3xl font-bold text-orange-600">{{ $snapshot->kpis['anomalies'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Alertes IA</p>
                    <p class="mt-2 text-3xl font-bold text-rose-600">{{ $snapshot->kpis['priority_alerts'] ?? 0 }}</p>
                </div>
            </div>

            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">Methode conservee</h2>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($snapshot->methodology as $method)
                        <div class="rounded-md bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-100">{{ $method }}</div>
                    @endforeach
                </div>
            </section>

            <section class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-950">Produits recommandes</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($snapshot->stock_predictions as $prediction)
                        <div class="px-6 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-semibold text-gray-950">{{ $prediction['product_name'] }}</p>
                                    <p class="mt-1 text-sm text-gray-600">Stock: {{ $prediction['current_quantity'] }} {{ $prediction['unit'] }} - recommande: {{ $prediction['recommended_quantity'] }} {{ $prediction['unit'] }}</p>
                                    <p class="mt-1 text-sm text-gray-600">Score {{ $prediction['priority_score'] }}/100 - confiance {{ $prediction['confidence'] }}</p>
                                    <p class="mt-2 text-sm font-medium text-gray-800">{{ $prediction['action'] }}</p>
                                </div>
                                <span class="w-fit rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $severityClasses[$prediction['severity']] ?? $severityClasses['stable'] }}">
                                    {{ $prediction['severity'] }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-gray-500">Aucun produit critique dans cet instantane.</div>
                    @endforelse
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-950">Anomalies</h2>
                    <div class="mt-4 space-y-4">
                        @forelse($snapshot->anomalies as $anomaly)
                            <div class="rounded-md border border-gray-100 p-4">
                                <p class="font-semibold text-gray-950">{{ $anomaly['title'] }}</p>
                                <p class="mt-1 text-sm text-gray-600">{{ $anomaly['message'] }}</p>
                                <p class="mt-2 text-sm font-medium text-gray-800">{{ $anomaly['recommendation'] }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Aucune anomalie.</p>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-950">Lots proches peremption</h2>
                    <div class="mt-4 space-y-4">
                        @forelse($snapshot->expiry_alerts as $alert)
                            <div class="rounded-md border border-gray-100 p-4">
                                <p class="font-semibold text-gray-950">{{ $alert['product_name'] }}</p>
                                <p class="mt-1 text-sm text-gray-600">Lot {{ $alert['batch_code'] }} - {{ $alert['remaining_quantity'] }} {{ $alert['unit'] }}</p>
                                <p class="mt-2 text-sm font-medium text-gray-800">{{ $alert['message'] }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Aucun lot critique.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
