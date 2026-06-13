@extends('manager.layouts.app')

@section('title', 'Top produits')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Top produits</h1>
            <p class="mt-2 text-sm text-gray-700">Classement des produits les plus vendus du {{ $dateFrom->format('d/m/Y') }} au {{ $dateTo->format('d/m/Y') }}.</p>
        </div>
        <a href="{{ route('manager.sales') }}" class="mt-4 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0">
            Retour aux ventes
        </a>
    </div>

    <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('manager.sales.top-products') }}" class="grid gap-4 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
            <div>
                <label for="period" class="block text-sm font-semibold text-gray-700">Periode</label>
                <select id="period" name="period" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500">
                    <option value="today" @selected($period === 'today')>Aujourd'hui</option>
                    <option value="7_days" @selected($period === '7_days')>7 jours</option>
                    <option value="30_days" @selected($period === '30_days')>30 jours</option>
                    <option value="month" @selected($period === 'month')>Mois courant</option>
                    <option value="custom" @selected($period === 'custom')>Personnalisee</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-sm font-semibold text-gray-700">Date debut</label>
                <input id="date_from" type="date" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-semibold text-gray-700">Date fin</label>
                <input id="date_to" type="date" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500">
            </div>
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700">
                Filtrer
            </button>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Rang</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Produit</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Categorie</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Quantite vendue</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Recette</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($products as $product)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold text-gray-900 sm:pl-6">{{ $products->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-4 text-sm">
                            <div class="font-semibold text-gray-900">{{ $product->name }}</div>
                            <div class="text-xs text-gray-500">SKU: {{ $product->sku ?? '-' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">{{ $product->category_name ?? 'Sans categorie' }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-rose-700">{{ number_format((float) $product->total_quantity, 0, ',', ' ') }} {{ $product->unit }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900">{{ number_format((float) $product->total_revenue, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">Aucun produit vendu sur cette periode.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
@endsection
