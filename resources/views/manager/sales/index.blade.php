@extends('manager.layouts.app')

@section('title', 'Ventes')

@section('content')
<div id="manager-sales-page" x-data data-silent-refresh class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Ventes</h1>
            <p class="mt-2 text-sm text-gray-700">Toutes les ventes réalisées par vos vendeurs</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
            <!-- Export Buttons -->
            <x-export-buttons
                :excelRoute="route('manager.sales.export.excel', request()->query())"
                :pdfRoute="route('manager.sales.export.pdf', request()->query())"
            />
</div>
    </div>

    <!-- Stats Cards -->
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-5">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ventes filtrees</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $stats['filtered_sales'] }}</div>
                                <div class="ml-2 text-sm text-gray-500">vente(s)</div>
                            </dd>
                            <dd class="mt-1 text-sm text-rose-600 font-semibold">
                                {{ number_format($stats['filtered_revenue'], 0, ',', ' ') }} FCFA
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Recette du jour</dt>
                            <dd>
                                <x-money-toggle
                                    :amount="number_format($stats['total_current_day_revenue'], 0, ',', ' ') . ' FCFA'"
                                    label="la recette du jour" />
                            </dd>
                            <dd class="mt-1 text-sm text-gray-500">
                                Selon les caisses ouvertes/fermées
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Recette d'hier</dt>
                            <dd>
                                <x-money-toggle
                                    :amount="number_format($stats['total_yesterday_revenue'], 0, ',', ' ') . ' FCFA'"
                                    label="la recette d'hier" />
                            </dd>
                            <dd class="mt-1 text-sm text-gray-500">
                                Recettes clôturées ou ventes d'hier
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm5 4h4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Solde Cash</dt>
                            <dd>
                                <x-money-toggle
                                    :amount="number_format($stats['total_cash_balance'], 0, ',', ' ') . ' FCFA'"
                                    label="le solde cash" />
                            </dd>
                            <dd class="mt-1 text-sm text-gray-500">
                                Caisse cumulée après clôture
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500 truncate">Paiements mobiles</dt>
                <dd>
                    <x-money-toggle
                        :amount="number_format($stats['total_mobile_money_balance'], 0, ',', ' ') . ' FCFA'"
                        label="le solde des paiements mobiles" />
                </dd>
                <dd class="mt-1 text-sm text-gray-500">Orange Money + MTN Momo</dd>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('manager.sales') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-5">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">N° Facture</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="INV-..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
            <div>
                <label for="seller_id" class="block text-sm font-medium text-gray-700">Vendeur</label>
                <select name="seller_id" id="seller_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous</option>
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>{{ $seller->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700">Mode de paiement</label>
                <select name="payment_method" id="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Espèces</option>
                    <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Orange Money</option>
                    <option value="mobile_money" {{ request('payment_method') == 'mobile_money' ? 'selected' : '' }}>MTN Momo</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">Date début</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">Date fin</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">N° Facture</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vendeur</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Articles</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Paiement</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($sales as $sale)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-mono font-medium text-gray-900 sm:pl-6">
                                        {{ $sale->invoice_number }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $sale->created_at->format('d/m/Y') }}<br>
                                        <span class="text-xs">{{ $sale->created_at->format('H:i') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                        {{ $sale->seller->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $sale->items->count() }} article(s)
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span class="font-semibold text-rose-600">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if($sale->payment_method === 'cash')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                                Espèces
                                            </span>
                                        @elseif($sale->payment_method === 'card')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Orange Money
                                            </span>
                                        @elseif($sale->payment_method === 'mobile_money')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                MTN Momo
                                            </span>
                                        @else
                                            <span class="text-gray-500">{{ $sale->payment_method }}</span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <button type="button"
                                                onclick="window.dispatchEvent(new CustomEvent('open-sale-modal', { detail: { id: {{ $sale->id }} } }))"
                                                class="text-indigo-600 hover:text-indigo-900">
                                            Détails
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Détails (inline pour chaque vente) -->
                                <tr x-data="{ open: false }" @open-sale-details-{{ $sale->id }}.window="open = true" x-show="open" x-cloak style="display: none;">
                                    <td colspan="7" class="p-0">
                                        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="open">
                                            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
                                                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
                                                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                                        <div class="sm:flex sm:items-start">
                                                            <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                                                <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">
                                                                    Détails de la vente {{ $sale->invoice_number }}
                                                                </h3>

                                                                <!-- Infos vente -->
                                                                <div class="grid grid-cols-2 gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
                                                                    <div>
                                                                        <p class="text-sm text-gray-500">Vendeur</p>
                                                                        <p class="font-medium">{{ $sale->seller->name ?? 'N/A' }}</p>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-sm text-gray-500">Date</p>
                                                                        <p class="font-medium">{{ $sale->created_at->format('d/m/Y à H:i') }}</p>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-sm text-gray-500">Mode de paiement</p>
                                                                        <p class="font-medium">
                                                                            @if($sale->payment_method === 'cash') Espèces
                                                                            @elseif($sale->payment_method === 'card') Orange Money
                                                                            @elseif($sale->payment_method === 'mobile_money') MTN Momo
                                                                            @else {{ $sale->payment_method }}
                                                                            @endif
                                                                        </p>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-sm text-gray-500">Total</p>
                                                                        <p class="font-semibold text-rose-600 text-lg">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</p>
                                                                    </div>
                                                                </div>

                                                                <!-- Articles -->
                                                                <div class="mt-4">
                                                                    <h4 class="font-medium text-gray-900 mb-2">Articles vendus</h4>
                                                                    <table class="min-w-full divide-y divide-gray-200">
                                                                        <thead class="bg-gray-50">
                                                                            <tr>
                                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Produit</th>
                                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Qté</th>
                                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Prix unitaire</th>
                                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Sous-total</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="bg-white divide-y divide-gray-200">
                                                                            @foreach($sale->items as $item)
                                                                            <tr>
                                                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $item->product->name ?? 'N/A' }}</td>
                                                                                <td class="px-3 py-2 text-sm text-gray-500">{{ $item->quantity }}</td>
                                                                                <td class="px-3 py-2 text-sm text-gray-500">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                                                                                <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>

                                                                @if($sale->notes)
                                                                <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                                                                    <p class="text-sm text-gray-500">Notes</p>
                                                                    <p class="text-sm text-gray-900">{{ $sale->notes }}</p>
                                                                </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                                        <button type="button" @click="open = false"
                                                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                                            Fermer
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucune vente trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @foreach($sales as $sale)
        <x-sale-details-modal :sale="$sale" />
    @endforeach

    <!-- Pagination -->
    @if($sales->hasPages())
    <div class="mt-6">
        {{ $sales->links() }}
    </div>
    @endif
</div>

@endsection
