@extends('superadmin.layouts.app')

@section('title', 'Ventes')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Toutes les Ventes</h1>
            <p class="mt-2 text-sm text-gray-700">Historique complet des ventes du système</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16">
            <x-export-buttons
                :excelRoute="route('superadmin.sales.export.excel', request()->query())"
                :pdfRoute="route('superadmin.sales.export.pdf', request()->query())" />
        </div>
    </div>

    <!-- Filtres -->
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('superadmin.sales') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="seller_id" class="block text-sm font-medium text-gray-700">Vendeur</label>
                <select name="seller_id" id="seller_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                    <option value="">Tous les vendeurs</option>
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                            {{ $seller->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">Date début</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">Date fin</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
            </div>
        </form>
    </div>

    <!-- Statistiques de la page -->
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Total des ventes affichées</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $sales->total() }}</dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Chiffre d'affaires</dt>
                <dd class="mt-1 text-3xl font-semibold text-green-600">{{ number_format($totalRevenue, 0, ',', ' ') }} FCFA</dd>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Vente moyenne</dt>
                <dd class="mt-1 text-3xl font-semibold text-blue-600">{{ number_format($averageSale, 0, ',', ' ') }} FCFA</dd>
            </div>
        </div>
    </div>

    <!-- Table des ventes -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">N° Facture</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vendeur</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Articles</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Mode</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($sales as $sale)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-sale-modal', { detail: { id: {{ $sale->id }} } }))" 
                                                class="text-green-600 hover:text-green-900 font-medium hover:underline">
                                            {{ $sale->invoice_number }}
                                        </button>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center mr-2">
                                                <span class="text-green-600 font-medium text-xs">{{ strtoupper(substr($sale->seller->name, 0, 2)) }}</span>
                                            </div>
                                            {{ $sale->seller->name }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $sale->items->count() }} article(s)
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                        {{ number_format($sale->total, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($sale->payment_method == 'cash')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Espèces
                                            </span>
                                        @elseif($sale->payment_method == 'card')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                </svg>
                                                Orange Money
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                MTN Momo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $sale->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>

                                <!-- Modal de détails pour chaque vente -->
                                <x-sale-details-modal :sale="$sale" />
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucune vente trouvée
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $sales->links() }}
    </div>
</div>
@endsection
