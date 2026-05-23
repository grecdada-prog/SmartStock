@extends('manager.layouts.app')

@section('title', 'Ventes')

@section('content')
<div id="manager-sales-page" x-data data-silent-refresh class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
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

    <!-- Stats Section with Summary -->
    <div class="smartstore-sticky-cards grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Ventes filtrees</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $stats['filtered_sales'] }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Montant total</p>
                    <p class="mt-1 text-lg font-semibold text-rose-600">{{ number_format($stats['filtered_revenue'], 0, ',', ' ') }} FCFA</p>
                </div>
            </div>
        </div>

        <x-money-stat-card
            title="Recette du jour"
            :amount="number_format($stats['total_current_day_revenue'], 0, ',', ' ') . ' FCFA'"
            label="la recette du jour"
            :footer="number_format($stats['today_sales']) . ' vente(s)'"
            value-class="text-lg font-semibold text-gray-700"
        />

        <x-money-stat-card
            title="Recette d'hier"
            :amount="number_format($stats['total_yesterday_revenue'], 0, ',', ' ') . ' FCFA'"
            label="la recette d'hier"
            :footer="number_format($stats['yesterday_sales']) . ' vente(s)'"
            value-class="text-lg font-semibold text-gray-700"
        />
        <span class="sr-only">Recette d'hier</span>

        <x-money-stat-card
            title="Solde Cash"
            :amount="number_format($stats['total_cash_balance'], 0, ',', ' ') . ' FCFA'"
            label="le solde cash"
            value-class="text-lg font-semibold text-gray-700"
        />

        <x-money-stat-card
            title="Paiements mobiles"
            :amount="number_format($stats['total_mobile_money_balance'], 0, ',', ' ') . ' FCFA'"
            label="les paiements mobiles"
            value-class="text-lg font-semibold text-gray-700"
        />
    </div>
    <!-- Filtres -->
    <div class="bg-white shadow rounded-lg p-4 border border-gray-200">
        <form method="GET" data-auto-filter action="{{ route('manager.sales') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">N° Factures</label>
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
            </div>
        </form>
    </div>
        </div>
    </div>

    <!-- Table -->
    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Nº FACTURE</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">DATE-HEURE</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">VENDEUR</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">ARTICLES</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">PAIEMENT</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">TOTAL</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($sales as $sale)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-mono font-medium text-gray-900 sm:pl-6">
                                        {{ $sale->invoice_number }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div>{{ $sale->created_at->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $sale->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                        {{ $sale->seller->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $sale->items->count() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
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
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-rose-600">
                                        {{ number_format($sale->total, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <button type="button"
                                                onclick="window.dispatchEvent(new CustomEvent('open-sale-modal', { detail: { id: {{ $sale->id }} } }))"
                                                class="px-3 py-1 rounded-md border border-blue-200 text-blue-600 hover:bg-blue-50 transition">
                                            Détails
                                        </button>
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
    <!-- Modals -->
    @foreach($sales as $sale)
        <x-sale-details-modal :sale="$sale" />
    @endforeach
</div>

@endsection
