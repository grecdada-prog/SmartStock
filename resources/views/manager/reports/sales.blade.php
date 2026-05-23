@extends('manager.layouts.app')

@section('title', 'Rapport des ventes')

@section('content')
@php
    $isGroupedByDate = $salesByDate->isNotEmpty();
    $detailSales = $isGroupedByDate ? collect() : collect($sales);

    $topProducts = $detailSales
        ->flatMap(fn ($sale) => $sale->items)
        ->filter(fn ($item) => $item->product)
        ->groupBy('product_id')
        ->map(function ($items) {
            $first = $items->first();

            return [
                'name' => $first->product->name,
                'quantity' => $items->sum('quantity'),
                'total' => $items->sum('subtotal'),
            ];
        })
        ->sortByDesc('total')
        ->take(6)
        ->values();

    $sellerPerformance = $detailSales
        ->groupBy(fn ($sale) => $sale->seller_id)
        ->map(function ($group) {
            $seller = $group->first()->seller;

            return [
                'name' => $seller->name ?? '—',
                'initials' => strtoupper(substr($seller->name ?? '?', 0, 2)),
                'sales_count' => $group->count(),
                'revenue' => $group->sum('total'),
            ];
        })
        ->sortByDesc('revenue')
        ->values();

    $activeSellersCount = $detailSales->pluck('seller_id')->unique()->filter()->count();

    $grossMargin = $detailSales
        ->flatMap(fn ($sale) => $sale->items)
        ->sum(function ($item) {
            if (! $item->product) {
                return 0;
            }

            return ($item->unit_price - (float) $item->product->purchase_price) * $item->quantity;
        });

    $revenueTrend = null;
    if (($stats['total_yesterday_revenue'] ?? 0) > 0) {
        $revenueTrend = round(
            ((($stats['total_current_day_revenue'] ?? 0) - $stats['total_yesterday_revenue']) / $stats['total_yesterday_revenue']) * 100,
            1
        );
    }

    $resultsCount = $isGroupedByDate ? $salesByDate->count() : $detailSales->count();
@endphp

<div class="reports-module">
    <div class="reports-header">
        <div>
            <h1 class="reports-header__title">Rapport des ventes</h1>
            <p class="reports-header__subtitle">
                Analyse des ventes réalisées par vos vendeurs — filtres, exports et synthèse en un coup d'œil.
            </p>
        </div>
        <div class="reports-actions">
            <a href="{{ route('manager.sales.export.excel', request()->query()) }}" class="reports-btn">
                <svg class="h-4 w-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
            <a href="{{ route('manager.reports.sales.export.pdf', request()->query()) }}" class="reports-btn reports-btn--primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Export PDF
            </a>
        </div>
    </div>

    <div class="reports-kpi-grid">
        <x-reports.kpi-card
            label="Ventes"
            :value="number_format($stats['filtered_sales'])"
            hint="transactions"
        />
        <x-reports.kpi-card
            label="Recette totale"
            wide
            :value="number_format($stats['filtered_revenue'], 0, ',', ' ') . ' FCFA'"
            :badge="$revenueTrend !== null ? (($revenueTrend >= 0 ? '+' : '') . $revenueTrend . '% vs hier') : null"
            :badge-trend="$revenueTrend !== null ? ($revenueTrend >= 0 ? 'up' : 'down') : null"
        />
        <x-reports.kpi-card
            label="Panier moyen"
            :value="number_format($stats['average_sale'] ?? 0, 0, ',', ' ') . ' FCFA'"
            hint="par vente"
        />
        <x-reports.kpi-card
            label="Marge brute"
            :value="number_format($grossMargin, 0, ',', ' ') . ' FCFA'"
            hint="estimée"
            value-class="reports-kpi__value--accent"
        />
        <x-reports.kpi-card
            label="Vendeurs actifs"
            :value="number_format($activeSellersCount)"
            hint="sur la période affichée"
        />
    </div>

    <form method="GET" action="{{ route('manager.reports.sales') }}" data-auto-filter class="reports-filters">
        <div class="reports-field">
            <label for="seller_id">Vendeur</label>
            <select name="seller_id" id="seller_id">
                <option value="">Tous les vendeurs</option>
                @foreach ($sellers as $seller)
                    <option value="{{ $seller->id }}" @selected(request('seller_id') == $seller->id)>{{ $seller->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="reports-field">
            <label for="date_from">Date début</label>
            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="reports-field">
            <label for="date_to">Date fin</label>
            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="reports-field">
            <label for="group_by">Affichage</label>
            <select name="group_by" id="group_by">
                <option value="">Détail</option>
                <option value="date" @selected(request('group_by') === 'date')>Par date</option>
            </select>
        </div>
        <div class="reports-filters__footer">
            <a href="{{ route('manager.reports.sales') }}" class="reports-btn">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Réinitialiser
            </a>
        </div>
    </form>

    @unless ($isGroupedByDate)
        <div class="reports-split">
            <details class="reports-panel reports-panel--collapsible">
                <summary class="reports-panel__head">
                    <span class="reports-panel__head-main">
                        <span class="reports-panel__chevron" aria-hidden="true">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <span class="reports-panel__title">Top produits vendus</span>
                    </span>
                </summary>
                <div class="reports-panel__body">
                @if ($topProducts->isEmpty())
                    <p class="reports-empty">Aucun produit sur cette page.</p>
                @else
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Qté</th>
                                <th class="reports-table__total">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $product)
                                <tr>
                                    <td>{{ $product['name'] }}</td>
                                    <td class="reports-table__muted">{{ number_format($product['quantity']) }}</td>
                                    <td class="reports-table__total">{{ number_format($product['total'], 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                </div>
            </details>

            <details class="reports-panel reports-panel--collapsible">
                <summary class="reports-panel__head">
                    <span class="reports-panel__head-main">
                        <span class="reports-panel__chevron" aria-hidden="true">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <span class="reports-panel__title">Performance par vendeur</span>
                    </span>
                </summary>
                <div class="reports-panel__body">
                @if ($sellerPerformance->isEmpty())
                    <p class="reports-empty">Aucun vendeur sur cette page.</p>
                @else
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Vendeur</th>
                                <th>Ventes</th>
                                <th class="reports-table__total">Recette</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sellerPerformance as $row)
                                <tr>
                                    <td>
                                        <div class="reports-seller">
                                            <span class="reports-seller__avatar">{{ $row['initials'] }}</span>
                                            <span>{{ $row['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="reports-table__muted">{{ number_format($row['sales_count']) }}</td>
                                    <td class="reports-table__total">{{ number_format($row['revenue'], 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                </div>
            </details>
        </div>
    @endunless

    <x-reports.panel title="Détail des transactions" :meta="$resultsCount . ' résultat(s)'">
        <table class="reports-table">
            <thead>
                <tr>
                    <th>Facture</th>
                    <th>Date</th>
                    <th>Vendeur</th>
                    <th>Articles</th>
                    @unless ($isGroupedByDate)
                        <th>Mode paiement</th>
                    @endunless
                    <th class="reports-table__total">Total</th>
                </tr>
            </thead>
            <tbody>
                @if ($isGroupedByDate)
                    @forelse ($salesByDate as $sale)
                        <tr>
                            <td class="reports-table__muted">—</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($sale->date)->format('d/m/Y') }}</td>
                            <td class="reports-table__muted">—</td>
                            <td>{{ number_format($sale->count) }}</td>
                            <td class="reports-table__total">{{ number_format($sale->revenue, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="reports-empty">Aucune vente trouvée.</td>
                        </tr>
                    @endforelse
                @else
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="reports-table__invoice">{{ $sale->invoice_number }}</td>
                            <td class="reports-table__muted">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="reports-seller">
                                    <span class="reports-seller__avatar">{{ strtoupper(substr($sale->seller->name ?? '?', 0, 2)) }}</span>
                                    <span>{{ $sale->seller->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="reports-table__muted">{{ $sale->items->count() }}</td>
                            <td>
                                <x-reports.payment-pill :method="$sale->payment_method" />
                            </td>
                            <td class="reports-table__total">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="reports-empty">Aucune vente trouvée.</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </x-reports.panel>

</div>
@endsection
