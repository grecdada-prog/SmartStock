@extends('manager.layouts.app')

@section('title', 'Produits en Stock Faible')

@section('content')
<div id="manager-low-stock-page" data-silent-refresh class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Produits en Stock Faible</h1>
            <p class="mt-2 text-sm text-gray-700">Produits dont le stock est inférieur ou égal au seuil d'alerte</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
<a href="{{ route('manager.products.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                Tous les produits
            </a>
        </div>
    </div>

    @if($products->count() > 0)
        <!-- Alert Banner -->
        <div class="mt-6 rounded-md bg-orange-50 p-4">
            <div class="flex">
                <div>
                    <h3 class="text-sm font-medium text-orange-800">Attention : {{ $products->count() }} produit(s) en stock faible</h3>
                    <div class="mt-2 text-sm text-orange-700">
                        <p>Ces produits ont atteint ou dépassé leur seuil d'alerte. Pensez à réapprovisionner rapidement.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
        </div>
    </div>

    <!-- Table -->
    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-visible sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-visible shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Produit</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Catégorie</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Stock actuel</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Seuil d'alerte</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Niveau</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($products as $product)
                                <tr class="{{ $product->isOutOfStock() ? 'bg-red-50' : 'bg-orange-50' }}">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                                <div class="text-gray-500">SKU: {{ $product->sku }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $product->category->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($product->isOutOfStock())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                0 {{ $product->unit }}
                                            </span>
                                        @else
                                            <span class="text-gray-900 font-medium">{{ $product->quantity }} {{ $product->unit }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $product->alert_quantity }} {{ $product->unit }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @php
                                            $percentage = $product->alert_quantity > 0 ? ($product->quantity / $product->alert_quantity) * 100 : 0;
                                        @endphp
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="h-2 rounded-full {{ $product->isOutOfStock() ? 'bg-red-600' : 'bg-orange-600' }}" style="width: {{ min($percentage, 100) }}%"></div>
                                            </div>
                                            <span class="text-xs {{ $product->isOutOfStock() ? 'text-red-600' : 'text-orange-600' }} font-medium">
                                                {{ number_format($percentage, 0) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
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
                                    <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <p class="text-lg font-medium text-gray-900">Excellent !</p>
                                            <p class="mt-1 text-sm text-gray-500">Aucun produit n'est en stock faible pour le moment.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
