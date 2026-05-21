@extends('manager.layouts.app')

@section('title', 'Historique des approvisionnements')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Historique des approvisionnements</h1>
            <p class="mt-2 text-sm text-gray-700">Consultez chaque entree de stock et ses details.</p>
        </div>
        <a href="{{ route('manager.stock.index') }}" class="mt-4 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0">
            Retour au stock
        </a>
    </div>

    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Date</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Produit</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Quantite</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix achat</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Prix vente</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Lot restant</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Code-barres</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($restocks as $movement)
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
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">
                                {{ $movement->selling_price !== null ? number_format($movement->selling_price, 0, ',', ' ').' FCFA' : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600">
                                {{ $movement->remaining_quantity !== null ? $movement->remaining_quantity.' '.($movement->product->unit ?? '') : '-' }}
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-600">{{ $movement->barcode ?? '-' }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                <a href="{{ route('manager.stock.restocks.show', $movement) }}" class="inline-flex rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-8 text-center text-sm text-gray-500">Aucun approvisionnement trouve.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($restocks->hasPages())
        <div class="mt-6">{{ $restocks->links() }}</div>
    @endif
</div>
@endsection
