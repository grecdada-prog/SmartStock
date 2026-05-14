@extends('seller.layouts.app')

@section('title', 'Détails du Produit')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">{{ $product->name }}</h1>
            <p class="mt-2 text-sm text-gray-700">Informations détaillées sur le produit</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('seller.products') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                &larr; Retour
            </a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informations générales -->
            <div>
                <h2 class="text-lg font-medium text-gray-900 mb-4">Informations Générales</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nom</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">SKU</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->sku }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Catégorie</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->category->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->description ?? 'Aucune description' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Informations de stock et prix -->
            <div>
                <h2 class="text-lg font-medium text-gray-900 mb-4">Stock et Prix</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Prix de vente</dt>
                        <dd class="mt-1 text-lg font-semibold text-rose-600">{{ number_format($product->selling_price, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Prix d'achat</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($product->purchase_price, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Quantité en stock</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($product->quantity <= 5)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ $product->quantity }} (Stock faible)
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                    {{ $product->quantity }}
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Seuil d'alerte</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->alert_threshold }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Statut -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900">Statut</h3>
                    <p class="text-sm text-gray-500">État actuel du produit</p>
                </div>
                <div class="flex items-center space-x-3">
                    @if($product->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                            Actif
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Inactif
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
