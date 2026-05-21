@extends('manager.layouts.app')

@section('title', 'Detail approvisionnement')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Detail de l'approvisionnement</h1>
                <p class="mt-2 text-sm text-gray-700">{{ $movement->product->name ?? 'Produit supprime' }}</p>
            </div>
            <a href="{{ route('manager.stock.restocks') }}" class="inline-flex w-fit rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Retour
            </a>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5">
            <dl class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Produit</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $movement->product->name ?? 'N/A' }}</dd>
                    <dd class="mt-1 text-xs text-gray-500">SKU: {{ $movement->product->sku ?? 'N/A' }}</dd>
                </div>
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Date de creation</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $movement->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>

            <dl class="grid grid-cols-1 gap-0 border-t border-gray-100 sm:grid-cols-3">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Quantite ajoutee</dt>
                    <dd class="mt-1 text-2xl font-semibold text-rose-700">+{{ $movement->quantity }} {{ $movement->product->unit ?? '' }}</dd>
                </div>
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Stock avant</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $movement->quantity_before }}</dd>
                </div>
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Stock apres</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $movement->quantity_after }}</dd>
                </div>
            </dl>

            <dl class="grid grid-cols-1 gap-0 border-t border-gray-100 sm:grid-cols-2">
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Prix d'achat unitaire</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $movement->purchase_price !== null ? number_format($movement->purchase_price, 0, ',', ' ').' FCFA' : '-' }}</dd>
                </div>
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Prix de vente unitaire</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $movement->selling_price !== null ? number_format($movement->selling_price, 0, ',', ' ').' FCFA' : '-' }}</dd>
                </div>
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Lot</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $movement->batch_code ?? '-' }}</dd>
                    <dd class="mt-1 text-xs text-gray-500">Restant: {{ $movement->remaining_quantity !== null ? $movement->remaining_quantity.' '.($movement->product->unit ?? '') : '-' }}</dd>
                </div>
                <div class="p-5">
                    <dt class="text-sm font-medium text-gray-500">Code-barres</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $movement->barcode ?? '-' }}</dd>
                </div>
            </dl>

            <div class="border-t border-gray-100 p-5">
                <p class="text-sm font-medium text-gray-500">Notes</p>
                <p class="mt-1 text-sm text-gray-700">{{ $movement->reason ?? '-' }}</p>
                <p class="mt-4 text-xs text-gray-500">Enregistre par {{ $movement->user->name ?? 'Systeme' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
