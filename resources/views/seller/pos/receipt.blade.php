@extends('seller.layouts.app')

@section('title', 'Reçu de Vente')

@section('content')
<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-md mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-green-600 text-white p-6 text-center">
            <h1 class="text-2xl font-bold">REÇU DE VENTE</h1>
            <p class="text-sm opacity-90">{{ config('app.name') }}</p>
        </div>

        <!-- Receipt Content -->
        <div class="p-6">
            <!-- Sale Info -->
            <div class="mb-6">
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium">N° Facture:</span>
                    <span>{{ $sale->invoice_number }}</span>
                </div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium">Date:</span>
                    <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium">Vendeur:</span>
                    <span>{{ $sale->seller->name ?? 'N/A' }}</span>
                </div>
                @if($sale->customer_name)
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium">Client:</span>
                    <span>{{ $sale->customer_name }}</span>
                </div>
                @endif
                @if($sale->customer_phone)
                <div class="flex justify-between text-sm">
                    <span class="font-medium">Téléphone:</span>
                    <span>{{ $sale->customer_phone }}</span>
                </div>
                @endif
            </div>

            <!-- Items -->
            <div class="border-t border-b border-gray-300 py-4 mb-4">
                <h3 class="font-medium text-gray-900 mb-3">Articles</h3>
                @foreach($sale->items as $item)
                <div class="flex justify-between items-center mb-2">
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ $item->product->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500">{{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Total -->
            <div class="space-y-2">
                <div class="flex justify-between text-lg font-bold">
                    <span>TOTAL:</span>
                    <span>{{ number_format($sale->total, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Méthode de paiement:</span>
                    <span>
                        @if($sale->payment_method === 'cash') Espèces
                        @elseif($sale->payment_method === 'card') Orange Money
                        @elseif($sale->payment_method === 'mobile_money') MTN Momo
                        @else {{ $sale->payment_method }}
                        @endif
                    </span>
                </div>
                @if($sale->payment_method === 'cash')
                <div class="flex justify-between text-sm">
                    <span>Montant reçu:</span>
                    <span>{{ number_format($sale->amount_received ?? $sale->total, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Monnaie rendue:</span>
                    <span>{{ number_format($sale->change_given ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
                @endif
            </div>

            @if($sale->notes)
            <div class="mt-4 p-3 bg-yellow-50 rounded">
                <p class="text-xs text-gray-600">Notes:</p>
                <p class="text-xs">{{ $sale->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 p-4 text-center">
            <p class="text-xs text-gray-500">Merci pour votre achat!</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <!-- Print Button -->
    <div class="max-w-md mx-auto mt-6 text-center">
        <button onclick="window.print()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
            Imprimer le Reçu
        </button>
        <a href="{{ route('seller.pos.index') }}" class="ml-4 bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
            Retour au POS
        </a>
    </div>
</div>

<style>
@media print {
    .bg-gray-100 { background: white !important; }
    button, a { display: none !important; }
    .min-h-screen { min-height: auto !important; }
}
</style>
@endsection
