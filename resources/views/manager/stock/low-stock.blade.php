@extends('manager.layouts.app')

@section('title', 'Stock faible')

@section('content')
<style>
    .smartstore-print-only {
        display: none;
    }

    @media print {
        .smartstore-no-print,
        nav,
        footer {
            display: none !important;
        }

        .smartstore-print-only {
            display: block !important;
        }

        body {
            background: #ffffff !important;
        }

        main {
            max-width: none !important;
            padding: 0 !important;
        }
    }
</style>

<div class="smartstore-no-print px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Stock faible</h1>
            <p class="mt-2 text-sm text-gray-700">Produits actifs dont la quantite est inferieure ou egale au seuil d'alerte.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
            <button type="button" onclick="window.print()" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Imprimer
            </button>
            <a href="{{ route('manager.stock.low-stock.pdf') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                PDF
            </a>
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
                    <h3 class="text-sm font-medium text-orange-800">{{ method_exists($products, 'total') ? $products->total() : $products->count() }} produit(s) a surveiller</h3>
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
                    <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($products as $product)
                    <tr class="{{ $product->isOutOfStock() ? 'bg-red-50' : 'bg-orange-50' }}">
                        <td class="py-4 pl-4 pr-3 text-sm sm:pl-6">
                            <div class="font-medium text-gray-900">{{ $product->name }}</div>
                            <div class="text-gray-500">Code-barres: {{ $product->barcode ?: '-' }}</div>
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500">{{ $product->category->name ?? 'N/A' }}</td>
                        <td class="px-3 py-4 text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $product->isOutOfStock() ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ $product->quantity }} {{ $product->unit }}
                            </span>
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500">{{ $product->alert_quantity }} {{ $product->unit }}</td>
                        <td class="px-3 py-4 text-right text-sm font-medium">
                            <a href="{{ route('manager.products.show', $product) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                Voir
                            </a>
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

<div class="smartstore-print-only px-6 py-6">
    <x-smartstore-pdf-logo
        title="Stock faible et ruptures"
        :subtitle="'Manager: ' . auth()->user()->name . ' - Impression: ' . now()->format('d/m/Y H:i')"
    />

    <table style="width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px;">
        <thead>
            <tr>
                <th style="border: 1px solid #d1d5db; padding: 6px; text-align: left;">Produit</th>
                <th style="border: 1px solid #d1d5db; padding: 6px; text-align: left;">Code-barres</th>
                <th style="border: 1px solid #d1d5db; padding: 6px; text-align: left;">Stock</th>
                <th style="border: 1px solid #d1d5db; padding: 6px; text-align: left;">Dernier prix d'achat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($printProducts as $product)
                <tr>
                    <td style="border: 1px solid #d1d5db; padding: 6px;">{{ $product->name }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 6px;">{{ $product->barcode ?: '-' }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 6px;">{{ $product->quantity }} {{ $product->unit }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 6px;">{{ number_format($product->latestPurchaseMovement->purchase_price ?? $product->purchase_price ?? 0, 0, ',', ' ') }} FCFA</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="border: 1px solid #d1d5db; padding: 10px; text-align: center;">Aucun produit en stock faible.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
