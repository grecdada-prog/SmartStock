@extends('manager.layouts.app')

@section('title', 'Historique des mouvements de stock')

@section('content')
@php
    $showProductColumn = ! $selectedProduct;
    $emptyColspan = $showProductColumn ? 9 : 8;
@endphp

<div id="manager-stock-movements-page" data-silent-refresh class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <div class="sm:flex sm:items-start sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">
                @if($selectedProduct)
                    Historique de {{ $selectedProduct->name }}
                @else
                    Historique des mouvements de stock
                @endif
            </h1>
            @if($selectedProduct)
                <p class="mt-2 text-sm text-gray-600">{{ $selectedProduct->sku }}</p>
            @endif
        </div>
        <div class="mt-4 flex flex-wrap gap-2 sm:ml-16 sm:mt-0">
            @if($selectedProduct)
                <a href="{{ route('manager.stock.movements') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Tous les produits
                </a>
            @endif
            <a href="{{ route('manager.stock.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Retour au Stock
            </a>
        </div>
    </div>

    <div class="rounded-lg bg-white p-5 shadow">
        <form method="GET" data-auto-filter action="{{ route('manager.stock.movements') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @if($selectedProduct)
                <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
            @else
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700">Produit</label>
                    <select name="product_id" id="product_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        <option value="">Tous les produits</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Entree</option>
                    <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Sortie</option>
                    <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Ajustement</option>
                    <option value="correction_cancellation" {{ request('type') == 'correction_cancellation' ? 'selected' : '' }}>Correction annulation</option>
                </select>
            </div>

            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">Date debut</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>

            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">Date fin</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
        </form>
    </div>
        </div>
    </div>

    <div class="mt-6 overflow-visible rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5">
        <div class="overflow-visible">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="w-32 px-5 py-4 text-left text-sm font-semibold text-gray-900">Date</th>
                        @if($showProductColumn)
                            <th scope="col" class="w-56 px-5 py-4 text-left text-sm font-semibold text-gray-900">Produit</th>
                        @endif
                        <th scope="col" class="w-44 px-5 py-4 text-left text-sm font-semibold text-gray-900">Type</th>
                        <th scope="col" class="w-32 px-5 py-4 text-left text-sm font-semibold text-gray-900">Quantite</th>
                        <th scope="col" class="w-32 px-5 py-4 text-left text-sm font-semibold text-gray-900">Stock</th>
                        <th scope="col" class="w-40 px-5 py-4 text-left text-sm font-semibold text-gray-900">Prix</th>
                        <th scope="col" class="w-48 px-5 py-4 text-left text-sm font-semibold text-gray-900">Reference</th>
                        <th scope="col" class="min-w-72 px-5 py-4 text-left text-sm font-semibold text-gray-900">Raison</th>
                        <th scope="col" class="w-36 px-5 py-4 text-left text-sm font-semibold text-gray-900">Utilisateur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($movements as $movement)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-600">
                                <div class="font-medium text-gray-900">{{ $movement->created_at->format('d/m/Y') }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $movement->created_at->format('H:i') }}</div>
                            </td>
                            @if($showProductColumn)
                                <td class="px-5 py-5 text-sm">
                                    <div class="font-medium text-gray-900">{{ $movement->product->name ?? 'N/A' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ $movement->product->sku ?? 'N/A' }}</div>
                                </td>
                            @endif
                            <td class="px-5 py-5 text-sm">
                                @if($movement->type === 'in')
                                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-800">
                                        <span class="mr-1.5 h-2 w-2 shrink-0 rounded-full bg-rose-400"></span>
                                        Entree
                                    </span>
                                @elseif($movement->type === 'out')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800">
                                        <span class="mr-1.5 h-2 w-2 shrink-0 rounded-full bg-red-400"></span>
                                        Sortie
                                    </span>
                                @elseif($movement->type === 'correction_cancellation')
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800 whitespace-nowrap">
                                        <span class="mr-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-400"></span>
                                        Correction annul.
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">
                                        <span class="mr-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-400"></span>
                                        Ajustement
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 text-sm">
                                <span class="{{ in_array($movement->type, ['in', 'correction_cancellation'], true) ? 'text-rose-600' : ($movement->type === 'out' ? 'text-red-600' : 'text-blue-600') }} font-semibold">
                                    {{ in_array($movement->type, ['in', 'correction_cancellation'], true) ? '+' : ($movement->type === 'out' ? '-' : '+/-') }}{{ $movement->quantity }}
                                </span>
                                <span class="text-gray-500">{{ $movement->product->unit ?? '' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-600">
                                {{ $movement->quantity_before }}
                                <span class="mx-1 text-gray-400">-></span>
                                <span class="font-semibold text-gray-900">{{ $movement->quantity_after }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-600">
                                <div>Achat: {{ $movement->purchase_price !== null ? number_format($movement->purchase_price, 0, ',', ' ').' FCFA' : '-' }}</div>
                                <div class="mt-1">Vente: {{ $movement->selling_price !== null ? number_format($movement->selling_price, 0, ',', ' ').' FCFA' : '-' }}</div>
                            </td>
                            <td class="px-5 py-5 text-sm text-gray-600">
                                <div class="max-w-44 break-words">{{ $movement->reference ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-5 text-sm text-gray-600">
                                <div class="max-w-md break-words leading-6">{{ $movement->reason ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 text-sm text-gray-600">
                                {{ $movement->user->name ?? 'Systeme' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $emptyColspan }}" class="px-5 py-10 text-center text-sm text-gray-500">
                                Aucun mouvement de stock trouve.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(method_exists($movements, 'links'))
        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    @endif

</div>
@endsection
