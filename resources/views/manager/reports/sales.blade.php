@extends('manager.layouts.app')

@section('title', 'Rapport des ventes')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Rapport des ventes</h1>
            <p class="mt-1 text-sm text-gray-600">Analyse des ventes realisees par vos vendeurs.</p>
        </div>
        <a href="{{ route('manager.reports.sales.export.pdf', request()->query()) }}" class="px-4 py-2 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">
            Export PDF
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Ventes filtrees</p><p class="mt-1 text-2xl font-semibold">{{ $stats['filtered_sales'] }}</p></div>
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Recette filtree</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['filtered_revenue'], 0, ',', ' ') }} FCFA</p></div>
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Panier moyen</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['average_sale'] ?? 0, 0, ',', ' ') }} FCFA</p></div>
        <div class="bg-white p-4 rounded-md shadow-sm border"><p class="text-sm text-gray-500">Recette du jour</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['total_current_day_revenue'], 0, ',', ' ') }} FCFA</p></div>
    </div>

    <form method="GET" data-auto-filter class="bg-white p-4 rounded-md shadow-sm border grid grid-cols-1 gap-4 md:grid-cols-5">
        <select name="seller_id" class="rounded-md border-gray-300">
            <option value="">Tous les vendeurs</option>
            @foreach($sellers as $seller)
                <option value="{{ $seller->id }}" @selected(request('seller_id') == $seller->id)>{{ $seller->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border-gray-300">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border-gray-300">
        <select name="group_by" class="rounded-md border-gray-300">
            <option value="">Detail</option>
            <option value="date" @selected(request('group_by') === 'date')>Par date</option>
        </select>
    </form>

    <div class="bg-white rounded-md shadow-sm border overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facture/Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendeur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Articles</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @if($salesByDate->isNotEmpty())
                    @foreach($salesByDate as $sale)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $sale->date }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">-</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $sale->count }}</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ number_format($sale->revenue, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                @else
                    @forelse($sales as $sale)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $sale->invoice_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $sale->seller->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $sale->items->count() }}</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Aucune vente trouvee.</td></tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>

    @if(method_exists($sales, 'links'))
        {{ $sales->links() }}
    @endif
</div>
@endsection
