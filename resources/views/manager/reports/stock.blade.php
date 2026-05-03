@extends('manager.layouts.app')

@section('title', 'Rapport stock')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Rapport stock</h1>
        <p class="mt-1 text-sm text-gray-600">Etat de votre inventaire et alertes de stock.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Produits</p><p class="mt-1 text-2xl font-semibold">{{ $stats['total_products'] }}</p></div>
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Stock faible</p><p class="mt-1 text-2xl font-semibold">{{ $stats['low_stock_products'] }}</p></div>
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Rupture</p><p class="mt-1 text-2xl font-semibold">{{ $stats['out_of_stock_products'] }}</p></div>
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Valeur</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['total_value'], 0, ',', ' ') }} FCFA</p></div>
    </div>

    <form method="GET" data-auto-filter class="bg-white p-4 rounded-md shadow-sm border grid grid-cols-1 gap-4 md:grid-cols-3">
        <select name="stock_status" class="rounded-md border-gray-300">
            <option value="">Tous les stocks</option>
            <option value="normal" @selected(request('stock_status') === 'normal')>Normal</option>
            <option value="low" @selected(request('stock_status') === 'low')>Stock faible</option>
            <option value="out" @selected(request('stock_status') === 'out')>Rupture</option>
        </select>
        <a href="{{ route('manager.stock.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-medium">Reapprovisionner</a>
    </form>

    <div class="bg-white rounded-md shadow-sm border overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categorie</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantite</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alerte</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valeur</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $product->category->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $product->quantity }} {{ $product->unit }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $product->alert_quantity }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($product->quantity * $product->purchase_price, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Aucun produit trouve.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</div>
@endsection
