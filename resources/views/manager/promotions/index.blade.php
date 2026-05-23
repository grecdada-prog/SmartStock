@extends('manager.layouts.app')

@section('title', 'Promotions')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div class="sm:flex-auto">
                    <h1 class="text-2xl font-semibold text-gray-900">Promotions</h1>
                    <p class="mt-1 text-sm text-gray-700">Prix speciaux applicables au point de vente.</p>
                </div>
                <div class="mt-3 sm:mt-0">
                    <a href="{{ route('manager.promotions.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter une promotion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 overflow-hidden rounded-lg bg-white shadow ring-1 ring-black/5">
        <div class="table-scroll table-scroll--page">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Promotion</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Produit</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Seuil</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Prix promo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($promotions as $promotion)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <div class="font-semibold text-gray-900">{{ $promotion->name }}</div>
                                <div class="text-xs text-gray-500">Creee le {{ $promotion->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">{{ $promotion->product->name ?? 'Produit supprime' }}</div>
                                <div class="text-xs text-gray-500">SKU: {{ $promotion->product->sku ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                A partir de {{ $promotion->min_quantity }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-rose-600">
                                {{ number_format($promotion->promotion_price, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if($promotion->isActive())
                                    <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Suspendue</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('manager.promotions.edit', $promotion) }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Modifier</a>
                                    <form method="POST" action="{{ route('manager.promotions.toggle-status', $promotion) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md border border-orange-300 bg-white px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-50">
                                            {{ $promotion->isActive() ? 'Suspendre' : 'Reactiver' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('manager.promotions.destroy', $promotion) }}" onsubmit="return confirm('Supprimer cette promotion ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                Aucune promotion creee.
                                <a href="{{ route('manager.promotions.create') }}" class="font-medium text-rose-600 hover:text-rose-700">Ajouter une promotion</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
