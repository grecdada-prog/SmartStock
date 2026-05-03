@extends('seller.layouts.app')

@section('title', 'Détails de la vente')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Détails de la vente {{ $sale->invoice_number }}</h1>
            <p class="mt-2 text-sm text-gray-700">Informations complètes sur la vente</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('seller.sales.history') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                &larr; Retour
            </a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Date</p>
                <p class="font-medium">{{ $sale->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Mode de paiement</p>
                <p class="font-medium">
                    @if($sale->payment_method === 'cash') Espèces
                    @elseif($sale->payment_method === 'card') Orange Money
                    @elseif($sale->payment_method === 'mobile_money') MTN Momo
                    @else {{ $sale->payment_method }}
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total</p>
                <p class="font-semibold text-green-600 text-lg">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</p>
            </div>
            @if($sale->payment_method === 'cash')
            <div>
                <p class="text-sm text-gray-500">Montant recu</p>
                <p class="font-medium">{{ number_format($sale->amount_received ?? $sale->total, 0, ',', ' ') }} FCFA</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Monnaie rendue</p>
                <p class="font-medium">{{ number_format($sale->change_given ?? 0, 0, ',', ' ') }} FCFA</p>
            </div>
            @endif
            @if($sale->customer_name || $sale->customer_phone)
            <div>
                <p class="text-sm text-gray-500">Client</p>
                <p class="font-medium">
                    {{ $sale->customer_name ?? '' }}
                    @if($sale->customer_phone) ({{ $sale->customer_phone }})@endif
                </p>
            </div>
            @endif
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Articles vendus</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Produit</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Qté</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Prix unitaire</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Sous-total</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($sale->items as $item)
                <tr>
                    <td class="px-3 py-2 text-sm text-gray-900">{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="px-3 py-2 text-sm text-gray-500">{{ $item->quantity }}</td>
                    <td class="px-3 py-2 text-sm text-gray-500">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                    <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($sale->notes)
    <div class="bg-yellow-50 p-4 rounded-lg">
        <p class="text-sm text-gray-500">Notes</p>
        <p class="text-sm text-gray-900">{{ $sale->notes }}</p>
    </div>
    @endif
</div>
@endsection
