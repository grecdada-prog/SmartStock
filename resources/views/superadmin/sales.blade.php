@extends('superadmin.layouts.app')

@section('title', 'Ventes')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Ventes</h1>
            <p class="mt-2 text-sm text-gray-700">Suivi global des ventes et des encaissements.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16">
            <x-export-buttons
                :excelRoute="route('superadmin.sales.export.excel', request()->query())"
                :pdfRoute="route('superadmin.sales.export.pdf', request()->query())" />
        </div>
    </div>

    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('superadmin.sales') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">N° Facture</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="INV-..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
            <div>
                <label for="seller_id" class="block text-sm font-medium text-gray-700">Vendeur</label>
                <select name="seller_id" id="seller_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous les vendeurs</option>
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                            {{ $seller->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700">Paiement</label>
                <select name="payment_method" id="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Especes</option>
                    <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Orange Money</option>
                    <option value="mobile_money" {{ request('payment_method') == 'mobile_money' ? 'selected' : '' }}>MTN Momo</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">Date debut</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">Date fin</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
        </form>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Ventes filtrees</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['filtered_sales'] }}</dd>
                <p class="mt-2 text-sm font-semibold text-rose-600">{{ number_format($stats['filtered_revenue'], 0, ',', ' ') }} FCFA</p>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Recette du jour</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($stats['total_current_day_revenue'], 0, ',', ' ') }} FCFA</dd>
                <p class="mt-2 text-sm text-gray-500">Ventes du jour non cloturees</p>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Recette d'hier</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($stats['total_yesterday_revenue'], 0, ',', ' ') }} FCFA</dd>
                <p class="mt-2 text-sm text-gray-500">Recette de la veille</p>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Solde Cash</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($stats['total_cash_balance'], 0, ',', ' ') }} FCFA</dd>
                <p class="mt-2 text-sm text-gray-500">Solde cumule des caisses</p>
            </div>
        </div>
    </div>

    <div class="mt-8 flex flex-col">
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
                                <tr class="hover:bg-gray-50 transition">
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
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                            {{ $sale->items->count() }} article(s)
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span class="font-semibold text-rose-600">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if($sale->payment_method === 'cash')
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">
                                                Especes
                                            </span>
                                        @elseif($sale->payment_method === 'card')
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                                Orange Money
                                            </span>
                                        @elseif($sale->payment_method === 'mobile_money')
                                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
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
                                            Details
                                        </button>
                                    </td>
                                </tr>

                                <x-sale-details-modal :sale="$sale" />
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucune vente trouvee
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($sales->hasPages())
        <div class="mt-6">
            {{ $sales->links() }}
        </div>
    @endif
</div>
@endsection
