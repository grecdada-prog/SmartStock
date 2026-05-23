@extends('manager.layouts.app')

@section('title', 'Stock faible')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Stock faible</h1>
            <p class="mt-2 text-sm text-gray-700">Produits actifs dont la quantite est inferieure ou egale au seuil d'alerte.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
            <a href="{{ route('manager.stock.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Tout le stock
            </a>
            <a href="{{ route('manager.stock.restock') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700">
                Reapprovisionner
            </a>
        </div>
    </div>

    @if($products->count() > 0)
        <div class="mt-6 rounded-md bg-orange-50 p-4">
            <div class="flex">
                <div>
                    <h3 class="text-sm font-medium text-orange-800">{{ $products->count() }} produit(s) a surveiller</h3>
                    <p class="mt-2 text-sm text-orange-700">Ces articles doivent être controles ou réapprovisionnés rapidement.</p>
                </div>
            </div>
        </div>
    @endif
        </div>
    </div>

    <div class="mt-6 overflow-visible shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Produit</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Categorie</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Stock</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Seuil</th>
                    <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($products as $product)
                    <tr class="{{ $product->isOutOfStock() ? 'bg-red-50' : 'bg-orange-50' }}">
                        <td class="py-4 pl-4 pr-3 text-sm sm:pl-6">
                            <div class="font-medium text-gray-900">{{ $product->name }}</div>
                            <div class="text-gray-500">SKU: {{ $product->sku }}</div>
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500">{{ $product->category->name ?? 'N/A' }}</td>
                        <td class="px-3 py-4 text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $product->isOutOfStock() ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ $product->quantity }} {{ $product->unit }}
                            </span>
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500">{{ $product->alert_quantity }} {{ $product->unit }}</td>
                        <td class="relative px-3 py-4 text-right text-sm font-medium">
                            <x-action-menu>
                                <a href="{{ route('manager.products.show', $product) }}" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 focus:bg-gray-50 focus:outline-none">
                                    Voir
                                </a>
                                <a href="{{ route('manager.products.edit', $product) }}" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-blue-700 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none">
                                    Modifier
                                </a>
                            </x-action-menu>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">
                            Aucun produit en stock faible pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
