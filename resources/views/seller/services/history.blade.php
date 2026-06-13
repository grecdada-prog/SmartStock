@extends('seller.layouts.app')

@section('title', 'Historique Dépôt / Retrait')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">

    <!-- Header sticky -->
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-semibold text-gray-900">Historique Dépôt / Retrait</h1>
                    <p class="mt-2 text-sm text-gray-700">Toutes vos opérations MOMO/OM</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <a href="{{ route('seller.services.index') }}"
                       class="inline-flex items-center gap-2 rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                        + Nouvelle opération
                    </a>
                </div>
            </div>

            <!-- Cartes totaux -->
            <div class="smartstore-sticky-cards grid grid-cols-2 gap-5 sm:grid-cols-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Dépôts</p>
                    <p class="mt-1 text-xl font-bold text-green-600">{{ $stats['count_depots'] }}</p>
                    <p class="text-sm text-gray-600">{{ number_format($stats['total_depots'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Retraits</p>
                    <p class="mt-1 text-xl font-bold text-red-600">{{ $stats['count_retraits'] }}</p>
                    <p class="text-sm text-gray-600">{{ number_format($stats['total_retraits'], 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 sm:col-span-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Solde net opérations</p>
                    @php $net = $stats['total_depots'] - $stats['total_retraits']; @endphp
                    <p class="mt-1 text-xl font-bold {{ $net >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($net >= 0 ? '+' : '') . number_format($net, 0, ',', ' ') }} FCFA
                    </p>
                    <p class="text-sm text-gray-500">Impact net sur caisse cash</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mt-5 bg-white shadow-sm sm:rounded-lg px-5 py-4">
        <form method="GET" action="{{ route('seller.services.history') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Du</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="rounded-md border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Au</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="rounded-md border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                <select name="operation" class="rounded-md border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Tous</option>
                    <option value="depot"   {{ request('operation') === 'depot'   ? 'selected' : '' }}>Dépôts</option>
                    <option value="retrait" {{ request('operation') === 'retrait' ? 'selected' : '' }}>Retraits</option>
                </select>
            </div>
            <button type="submit"
                    class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Filtrer
            </button>
            @if(request()->hasAny(['date_from','date_to','operation']))
                <a href="{{ route('seller.services.history') }}"
                   class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    <!-- Tableau -->
    <div class="mt-4 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        @if($operations->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-500">
                Aucune opération trouvée.
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Opération</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Impact caisses</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Motif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($operations as $op)
                        @php
                            $isDepot = $op->type === 'add'; // ligne cash : add = dépôt
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $op->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $isDepot ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $isDepot ? 'Dépôt' : 'Retrait' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right whitespace-nowrap">
                                {{ number_format($op->amount, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                <span class="{{ $isDepot ? 'text-green-600' : 'text-red-600' }}">
                                    Cash {{ $isDepot ? '↑' : '↓' }}
                                </span>
                                &nbsp;/&nbsp;
                                <span class="{{ $isDepot ? 'text-red-600' : 'text-green-600' }}">
                                    MOMO {{ $isDepot ? '↓' : '↑' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                {{ $op->reason ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($operations->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $operations->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
