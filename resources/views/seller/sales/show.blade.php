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
            @if($sale->customer_phone)
            <div>
                <p class="text-sm text-gray-500">Numéro de téléphone</p>
                <p class="font-medium">{{ $sale->customer_phone }}</p>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-500">Total</p>
                <p class="font-semibold text-rose-600 text-lg">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</p>
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
            @if($sale->customer_name)
            <div>
                <p class="text-sm text-gray-500">Client</p>
                <p class="font-medium">{{ $sale->customer_name }}</p>
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
                @php($promotionDetails = $item->promotion_details)
                <tr>
                    <td class="px-3 py-2 text-sm text-gray-900">
                        <div class="font-semibold">{{ $item->display_name }}</div>
                        @if(($item->service_payload['token'] ?? null))
                            <div class="text-xs text-gray-500">Token: {{ $item->service_payload['token'] }}</div>
                        @endif
                        @if($item->service_payload['send_sms'] ?? false)
                            <div class="text-xs text-gray-500">
                                SMS: {{ $item->service_payload['sms_phone'] ?? 'Oui' }}
                                @if(($item->service_payload['sms_fee'] ?? 0) > 0)
                                    - {{ number_format($item->service_payload['sms_fee'], 0, ',', ' ') }} FCFA
                                @endif
                            </div>
                        @endif
@if($promotionDetails)
    <div class="mt-2 rounded-md border border-rose-100 bg-rose-50 px-2.5 py-2 text-xs text-rose-900">
        <div class="font-extrabold text-rose-700">Promo: {{ $promotionDetails['name'] }}</div>
        <div class="mt-1">
            @if($promotionDetails['original_unit_price'])
                <span>Prix normal: <span class="line-through">{{ number_format($promotionDetails['original_unit_price'], 0, ',', ' ') }} FCFA</span></span>
            @endif
            <span class="ml-2">Prix promo: <span class="font-bold">{{ number_format($promotionDetails['promotion_price'], 0, ',', ' ') }} FCFA</span></span>
            <span class="ml-2">Minimum: {{ $promotionDetails['min_quantity'] }}</span>
            @if($promotionDetails['discount_amount'] > 0)
                <span class="ml-2">Remise: <span class="font-bold">{{ number_format($promotionDetails['discount_amount'], 0, ',', ' ') }} FCFA</span></span>
            @endif
        </div>
    </div>
@endif
                    </td>
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
