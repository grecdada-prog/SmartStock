@extends('manager.layouts.app')

@section('title', 'Produits perimes')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-semibold text-gray-900">Produits perimes et proches de peremption</h1>
                    <p class="mt-2 text-sm text-gray-700">Lots encore en stock dont la date de peremption est depassee ou arrive dans les {{ $stats['days'] }} prochains jours.</p>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2 sm:mt-0 sm:ml-16">
                    <a href="{{ route('manager.dashboard') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Dashboard
                    </a>
                    <a href="{{ route('manager.stock.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Tout le stock
                    </a>
                    <a href="{{ route('manager.stock.restock') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700">
                        Reapprovisionner
                    </a>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <dt class="text-sm font-medium text-gray-500">Lots alertes</dt>
                    <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($stats['total']) }}</dd>
                </div>
                <div class="rounded-lg border border-red-100 bg-red-50 p-4 shadow-sm">
                    <dt class="text-sm font-medium text-red-700">Perimes</dt>
                    <dd class="mt-2 text-2xl font-semibold text-red-700">{{ number_format($stats['expired']) }}</dd>
                </div>
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 shadow-sm">
                    <dt class="text-sm font-medium text-amber-700">Proches</dt>
                    <dd class="mt-2 text-2xl font-semibold text-amber-700">{{ number_format($stats['soon']) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <dt class="text-sm font-medium text-gray-500">Quantite restante</dt>
                    <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($stats['remaining_quantity']) }}</dd>
                </div>
            </div>

            <div class="mt-6 rounded-lg bg-white p-4 shadow">
                <form method="GET" data-auto-filter action="{{ route('manager.stock.expiry-alerts') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Produit, code-barres, lot..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                            <option value="all" @selected($status === 'all')>Tous</option>
                            <option value="expired" @selected($status === 'expired')>Perimes</option>
                            <option value="soon" @selected($status === 'soon')>Proches de peremption</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <a href="{{ route('manager.stock.expiry-alerts') }}" class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">
                            Reinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-[1120px] divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Produit</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Lot</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Peremption</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Quantite</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix achat</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix vente</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Reference</th>
                    <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($movements as $movement)
                    @php
                        $expirationDate = $movement->expiration_date;
                        $isExpired = $expirationDate?->isPast() && ! $expirationDate?->isToday();
                        $daysLeft = $expirationDate ? today()->diffInDays($expirationDate, false) : null;
                    @endphp
                    <tr class="{{ $isExpired ? 'bg-red-50/70' : 'bg-white' }}">
                        <td class="py-4 pl-4 pr-3 text-sm sm:pl-6">
                            <div class="font-semibold text-gray-900">{{ $movement->product->name ?? 'Produit supprime' }}</div>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ $movement->product->category->name ?? 'Sans categorie' }}
                                @if($movement->product?->barcode)
                                    - Code-barres: {{ $movement->product->barcode }}
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">{{ $movement->batch_code ?? 'LOT-'.$movement->id }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ $expirationDate?->format('d/m/Y') }}</div>
                            <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $isExpired ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $isExpired ? 'Perime' : ($daysLeft === 0 ? 'Expire aujourd hui' : 'J-'.$daysLeft) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900">
                            {{ number_format($movement->remaining_quantity) }} {{ $movement->product->unit ?? '' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ number_format($movement->purchase_price ?? 0, 0, ',', ' ') }} FCFA</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ number_format($movement->selling_price ?? 0, 0, ',', ' ') }} FCFA</td>
                        <td class="px-3 py-4 text-sm text-gray-500">
                            <div class="max-w-xs truncate">{{ $movement->reference ?: '-' }}</div>
                            <div class="mt-1 max-w-xs truncate text-xs">{{ $movement->reason ?: '-' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-medium">
                            @if($movement->product)
                                <a href="{{ route('manager.promotions.create', ['product_id' => $movement->product_id]) }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-rose-700">
                                    Ajouter une promotion
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-8 text-center text-sm text-gray-500">
                            Aucun produit perime ou proche de peremption.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $movements->links() }}
    </div>
</div>
@endsection
