@extends('manager.layouts.app')

@section('title', 'Historique des approvisionnements')

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="{ showExportModal: false }">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Historique des approvisionnements</h1>
            <p class="mt-2 text-sm text-gray-700">Consultez chaque entree de stock et ses details.</p>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2 sm:mt-0">
            <button type="button" @click="showExportModal = true" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">
                Imprimer
            </button>
            <a href="{{ route('manager.stock.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Retour au stock
            </a>
        </div>
    </div>
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('manager.stock.restocks') }}" class="grid gap-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
            <div>
                <label for="date_from" class="block text-sm font-semibold text-gray-700">Date debut</label>
                <input id="date_from" type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-semibold text-gray-700">Date fin</label>
                <input id="date_to" type="date" name="date_to" value="{{ $dateTo }}" min="{{ $dateFrom }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500">
            </div>
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700">
                Filtrer
            </button>
            <a href="{{ route('manager.stock.restocks') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">
                Reinitialiser
            </a>
        </form>
        @if(! $dateFrom && ! $dateTo)
            <p class="mt-3 text-sm text-gray-500">Affichage des 30 dernieres transactions.</p>
        @endif
    </div>

    <div class="mt-6 overflow-visible rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5">
        <div class="overflow-visible">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Date</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Produit</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Quantite</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix achat</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Valeur</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix vente</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Lot restant</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Code-barres</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($restocks as $movement)
                        @php($movementValue = (float) $movement->quantity * (float) ($movement->purchase_price ?? 0))
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 sm:pl-6">
                                {{ $movement->created_at->format('d/m/Y') }}<br>
                                <span class="text-xs">{{ $movement->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-3 py-4 text-sm">
                                <div class="font-medium text-gray-900">{{ $movement->product->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">SKU: {{ $movement->product->sku ?? 'N/A' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-rose-700">
                                +{{ $movement->quantity }} {{ $movement->product->unit ?? '' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">
                                {{ $movement->purchase_price !== null ? number_format($movement->purchase_price, 0, ',', ' ').' FCFA' : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900">
                                {{ number_format($movementValue, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">
                                {{ $movement->selling_price !== null ? number_format($movement->selling_price, 0, ',', ' ').' FCFA' : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">
                                {{ $movement->remaining_quantity !== null ? $movement->remaining_quantity.' '.($movement->product->unit ?? '') : '-' }}
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-600">{{ $movement->product->barcode ?? '-' }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                <a href="{{ route('manager.stock.restocks.show', $movement) }}" class="inline-flex rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-8 text-center text-sm text-gray-500">Aucun approvisionnement trouve.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="py-3.5 pl-4 pr-3 text-right text-sm font-bold text-gray-900 sm:pl-6">Total valeur</td>
                        <td class="whitespace-nowrap px-3 py-3.5 text-sm font-extrabold text-rose-700">{{ number_format($totalValue, 0, ',', ' ') }} FCFA</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if(method_exists($restocks, 'links'))
        <div class="mt-4">
            {{ $restocks->links() }}
        </div>
    @endif

    <div x-show="showExportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6" style="display: none;">
        <div class="fixed inset-0 bg-gray-900/45" @click="showExportModal = false"></div>
        <div class="relative w-full max-w-lg rounded-lg bg-white shadow-xl">
            <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-950">Exporter les approvisionnements</h2>
                    <p class="mt-1 text-sm text-gray-500">Choisissez une date ou un intervalle precis.</p>
                </div>
                <button type="button" @click="showExportModal = false" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
            </div>
            <div class="px-5 py-5" x-data="{ from: '{{ now()->toDateString() }}', to: '' }">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="export_date_from" class="block text-sm font-bold text-gray-800">Date debut *</label>
                        <input id="export_date_from" type="date" x-model="from" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500">
                    </div>
                    <div>
                        <label for="export_date_to" class="block text-sm font-bold text-gray-800">Date fin</label>
                        <input id="export_date_to" type="date" x-model="to" :min="from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500">
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="showExportModal = false" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">
                        Annuler
                    </button>
                    <a :href="'{{ route('manager.stock.restocks.export.excel') }}?date_from=' + encodeURIComponent(from) + '&date_to=' + encodeURIComponent(to || '')"
                       class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50"
                       :class="!from ? 'pointer-events-none opacity-50' : ''">
                        Excel
                    </a>
                    <a :href="'{{ route('manager.stock.restocks.export.pdf') }}?date_from=' + encodeURIComponent(from) + '&date_to=' + encodeURIComponent(to || '')"
                       class="inline-flex justify-center rounded-md bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-rose-700"
                       :class="!from ? 'pointer-events-none opacity-50' : ''">
                        PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
