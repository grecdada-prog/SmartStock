@extends('manager.layouts.app')

@section('title', 'Rapport stock')

@section('content')
<div class="reports-module">
    <div class="reports-header">
        <div>
            <h1 class="reports-header__title">Rapport de stock</h1>
            <p class="reports-header__subtitle">État de votre inventaire et alertes de stock.</p>
        </div>
        <div class="reports-actions">
            <a href="{{ route('manager.stock.index') }}" class="reports-btn reports-btn--primary">Réapprovisionner</a>
        </div>
    </div>

    <div class="reports-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
        <x-reports.kpi-card label="Produits" :value="number_format($stats['total_products'])" />
        <x-reports.kpi-card label="Stock faible" :value="number_format($stats['low_stock_products'])" />
        <x-reports.kpi-card label="Rupture" :value="number_format($stats['out_of_stock_products'])" />
        <x-reports.kpi-card
            label="Valeur stock"
            :value="number_format($stats['total_value'], 0, ',', ' ') . ' FCFA'"
            value-class="reports-kpi__value--accent"
        />
    </div>

    <form method="GET" action="{{ route('manager.reports.stock') }}" data-auto-filter class="reports-filters" style="grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
        <div class="reports-field">
            <label for="stock_status">État du stock</label>
            <select name="stock_status" id="stock_status">
                <option value="">Tous les stocks</option>
                <option value="normal" @selected(request('stock_status') === 'normal')>Normal</option>
                <option value="low" @selected(request('stock_status') === 'low')>Stock faible</option>
                <option value="out" @selected(request('stock_status') === 'out')>Rupture</option>
            </select>
        </div>
        <div class="reports-filters__footer">
            <a href="{{ route('manager.reports.stock') }}" class="reports-btn">Réinitialiser</a>
        </div>
    </form>

    <x-reports.panel title="Inventaire" :meta="$products->count() . ' produit(s)'">
        <table class="reports-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Quantité</th>
                    <th>Alerte</th>
                    <th class="reports-table__total">Valeur</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="font-semibold">{{ $product->name }}</td>
                        <td class="reports-table__muted">{{ $product->category->name ?? '—' }}</td>
                        <td>{{ $product->quantity }} {{ $product->unit }}</td>
                        <td class="reports-table__muted">{{ $product->alert_quantity }}</td>
                        <td class="reports-table__total">{{ number_format($product->quantity * $product->purchase_price, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="reports-empty">Aucun produit trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </x-reports.panel>

</div>
@endsection
