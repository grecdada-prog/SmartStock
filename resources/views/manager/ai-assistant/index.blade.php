@extends('manager.layouts.app')

@section('title', 'SmartStore AI Assistant')

@section('content')
    @php
        $severityClasses = [
            'critical' => 'bg-red-50 text-red-700 ring-red-200',
            'high' => 'bg-orange-50 text-orange-700 ring-orange-200',
            'medium' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'stable' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
        $severityLabels = [
            'critical' => 'Critique',
            'high' => 'Eleve',
            'medium' => 'Moyen',
            'stable' => 'Stable',
        ];
        $confidenceClasses = [
            'high' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'medium' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'low' => 'bg-gray-50 text-gray-700 ring-gray-200',
        ];
        $confidenceLabels = [
            'high' => 'Haute',
            'medium' => 'Moyenne',
            'low' => 'Faible',
        ];
    @endphp

    <div class="py-2">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-rose-600">SmartStore AI Assistant</p>
                            <h1 class="mt-1 text-2xl font-bold text-gray-950">Aide intelligente a la decision</h1>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
                                Analyse automatique des ventes, du stock, des lots et des activites vendeurs pour anticiper les ruptures,
                                proposer les approvisionnements et signaler les anomalies.
                            </p>
                        </div>
                        <div class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
                            <span class="font-semibold text-gray-900">Analyse :</span>
                            {{ $analysis['period_days'] }} derniers jours
                            <span class="mx-2 text-gray-300">|</span>
                            {{ $analysis['generated_at']->format('d/m/Y H:i') }}
                        </div>
                        <a href="{{ route('manager.ai-assistant.export-pdf') }}"
                            class="inline-flex items-center justify-center rounded-md bg-gray-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                            Exporter rapport IA
                        </a>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <p class="text-base leading-7 text-gray-800">{{ $analysis['narrative'] }}</p>
                </div>
                <div class="border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Methode IA explicable</p>
                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach($analysis['methodology'] as $method)
                            <div class="rounded-md bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-100">
                                {{ $method }}
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                        <span class="font-semibold">Limites et fiabilite :</span>
                        les recommandations dependent de la qualite des ventes enregistrees, des stocks reellement saisis et de la regularite de l historique.
                        La confiance faible, moyenne ou haute indique si la recommandation doit etre appliquee directement ou verifiee avant action.
                    </div>
                </div>
            </div>

            <section class="mb-6 overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-950">Historique court des analyses IA</h2>
                    <p class="mt-1 text-sm text-gray-500">Chaque consultation enregistre un instantane auditable des recommandations affichees.</p>
                </div>
                <div class="grid grid-cols-1 divide-y divide-gray-100 md:grid-cols-5 md:divide-x md:divide-y-0">
                    @forelse($latestSnapshots as $historicalSnapshot)
                        <div class="p-4">
                            <p class="text-sm font-semibold text-gray-950">{{ $historicalSnapshot->generated_at->format('d/m H:i') }}</p>
                            <p class="mt-1 text-xs text-gray-500">Ruptures: {{ $historicalSnapshot->kpis['critical_stock'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Reappro: {{ $historicalSnapshot->kpis['restock_recommendations'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Anomalies: {{ $historicalSnapshot->kpis['anomalies'] ?? 0 }}</p>
                        </div>
                    @empty
                        <div class="p-6 text-sm text-gray-500 md:col-span-5">Aucune analyse historisee pour le moment.</div>
                    @endforelse
                </div>
            </section>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Ruptures critiques</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ number_format($analysis['kpis']['critical_stock']) }}</p>
                    <p class="mt-1 text-sm text-gray-500">Produits a surveiller en priorite</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Reapprovisionnements</p>
                    <p class="mt-2 text-3xl font-bold text-gray-950">{{ number_format($analysis['kpis']['restock_recommendations']) }}</p>
                    <p class="mt-1 text-sm text-gray-500">Quantites conseillees par l assistant</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Lots a expiration proche</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">{{ number_format($analysis['kpis']['expiry_alerts']) }}</p>
                    <p class="mt-1 text-sm text-gray-500">Sur les 10 prochains jours</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Anomalies detectees</p>
                    <p class="mt-2 text-3xl font-bold text-orange-600">{{ number_format($analysis['kpis']['anomalies']) }}</p>
                    <p class="mt-1 text-sm text-gray-500">Actions qui meritent verification</p>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Ventes du jour</p>
                    <p class="mt-2 text-2xl font-bold text-gray-950">{{ number_format($analysis['sales_summary']['today_sales']) }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ number_format($analysis['sales_summary']['today_revenue'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Recette du mois</p>
                    <p class="mt-2 text-2xl font-bold text-gray-950">{{ number_format($analysis['sales_summary']['month_revenue'], 0, ',', ' ') }} FCFA</p>
                    <p class="mt-1 text-sm text-gray-500">Toutes ventes des vendeurs du manager</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Produit leader aujourd hui</p>
                    <p class="mt-2 truncate text-2xl font-bold text-gray-950">{{ $analysis['sales_summary']['top_product_name'] ?? 'Aucun' }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ number_format($analysis['sales_summary']['top_product_quantity']) }} unite(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <section class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-950">Previsions de rupture et reapprovisionnement</h2>
                        <p class="mt-1 text-sm text-gray-500">Calcul base sur les ventes moyennes et un objectif de {{ $analysis['target_days'] }} jours de couverture.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Produit</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Rupture</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Priorite</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Conseil</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Niveau</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($analysis['stock_predictions'] as $prediction)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <p class="font-semibold text-gray-950">{{ $prediction['product']->name }}</p>
                                            <p class="text-sm text-gray-500">Stock: {{ $prediction['product']->quantity }} {{ $prediction['product']->unit }} - {{ $prediction['category'] }}</p>
                                            <p class="text-sm text-gray-500">Vendu: {{ number_format($prediction['sold_quantity']) }} sur 30 j, dont {{ number_format($prediction['recent_quantity']) }} sur 7 j</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $prediction['reason'] }}</p>
                                            <p class="mt-2 text-xs font-semibold text-gray-700">{{ $prediction['action'] }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                            {{ $prediction['days_to_stockout'] === null ? 'Non estimee' : $prediction['days_to_stockout'].' j' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <p class="text-sm font-bold text-gray-950">{{ $prediction['priority_score'] }}/100</p>
                                            <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $confidenceClasses[$prediction['confidence']] ?? $confidenceClasses['low'] }}">
                                                Confiance {{ $confidenceLabels[$prediction['confidence']] ?? 'Faible' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-950">
                                            {{ number_format($prediction['recommended_quantity']) }} {{ $prediction['product']->unit }}
                                            @if($prediction['recommended_quantity'] > 0)
                                                <a href="{{ route('manager.stock.restock', ['product_id' => $prediction['product']->id, 'quantity' => $prediction['recommended_quantity'], 'source' => 'ai']) }}"
                                                    class="mt-2 inline-flex rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                                                    Reapprovisionner
                                                </a>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $severityClasses[$prediction['severity']] ?? $severityClasses['stable'] }}">
                                                {{ $severityLabels[$prediction['severity']] ?? 'Stable' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">Aucune prevision critique pour le moment.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-950">Anomalies et conseils IA</h2>
                        <p class="mt-1 text-sm text-gray-500">Signaux faibles detectes automatiquement dans les ventes, stocks et caisses.</p>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse($analysis['anomalies'] as $anomaly)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-gray-950">{{ $anomaly['title'] }}</p>
                                        <p class="mt-1 text-sm text-gray-600">{{ $anomaly['message'] }}</p>
                                        <p class="mt-2 text-sm font-medium text-gray-800">{{ $anomaly['recommendation'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $severityClasses[$anomaly['severity']] ?? $severityClasses['medium'] }}">
                                        {{ $severityLabels[$anomaly['severity']] ?? 'Moyen' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-gray-500">Aucune anomalie detectee.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="mt-5 overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-950">Lots proches de la peremption</h2>
                    <p class="mt-1 text-sm text-gray-500">L assistant croise les lots restants avec les dates d expiration pour aider a prioriser les actions.</p>
                </div>
                <div class="grid grid-cols-1 divide-y divide-gray-100 md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-3">
                    @forelse($analysis['expiry_alerts'] as $alert)
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-950">{{ $alert['product']->name }}</p>
                                    <p class="mt-1 text-sm text-gray-500">Lot {{ $alert['movement']->batch_code ?? $alert['movement']->id }}</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $alert['movement']->remaining_quantity }} {{ $alert['product']->unit }} restant(s)</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $severityClasses[$alert['severity']] ?? $severityClasses['medium'] }}">
                                    {{ $alert['message'] }}
                                </span>
                            </div>
                            <p class="mt-4 text-sm text-gray-600">Expiration: {{ $alert['movement']->expiration_date?->format('d/m/Y') }}</p>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">Aucun lot proche de la peremption.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
