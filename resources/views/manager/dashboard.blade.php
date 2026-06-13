@extends('manager.layouts.app')

@section('title', 'Dashboard Gérant')

@section('content')
    <div class="py-2" x-data="{ showCash: false, showMobile: false, showToday: false }">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="smartstore-sticky-inner">
            <div class="smartstore-sticky-cards grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="money-amount-row">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Caisse MOMO/OM</p>
                            <p class="mt-2 break-words text-2xl font-semibold leading-tight text-gray-900" x-text="showMobile ? '{{ number_format($stats['total_mobile_money_balance'], 0, ',', ' ') }} FCFA' : '******'"></p>
                        </div>
                        <x-money-eye-button state="showMobile" label="le caisse MOMO/OM" />
                    </div>
                    <div class="mt-4 max-h-40 overflow-y-auto overflow-x-hidden divide-y divide-gray-100 text-sm">
                        @forelse($sellerFinancials as $row)
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2">
                                <a href="{{ route('manager.sellers.show', $row['seller']) }}" class="min-w-0 flex-1 truncate text-gray-600 hover:text-rose-700">{{ $row['seller']->name }}</a>
                                <div class="flex w-full flex-wrap items-center justify-between gap-2 sm:w-auto sm:justify-end">
                                    <span class="whitespace-nowrap font-medium text-gray-900" x-text="showMobile ? '{{ number_format($row['mobile_money_balance'], 0, ',', ' ') }} FCFA' : '******'"></span>
                                    <a href="{{ route('manager.sellers.show', $row['seller']) }}" class="whitespace-nowrap rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-700">Ajouter/Retirer</a>
                                </div>
                            </div>
                        @empty
                            <p class="py-2 text-gray-500">Aucun vendeur.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="money-amount-row">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Caisse Cash</p>
                            <p class="mt-2 break-words text-2xl font-semibold leading-tight text-gray-900" x-text="showCash ? '{{ number_format($stats['total_cash_balance'], 0, ',', ' ') }} FCFA' : '******'"></p>
                        </div>
                        <x-money-eye-button state="showCash" label="le Caisse Cash" refresh-on-show />
                    </div>
                    <div class="mt-4 max-h-40 overflow-y-auto overflow-x-hidden divide-y divide-gray-100 text-sm">
                        @forelse($sellerFinancials as $row)
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2">
                                <a href="{{ route('manager.sellers.show', $row['seller']) }}" class="min-w-0 flex-1 truncate text-gray-600 hover:text-rose-700">{{ $row['seller']->name }}</a>
                                <div class="flex w-full flex-wrap items-center justify-between gap-2 sm:w-auto sm:justify-end">
                                    <span class="whitespace-nowrap font-medium text-gray-900" x-text="showCash ? '{{ number_format($row['cash_balance'], 0, ',', ' ') }} FCFA' : '******'"></span>
                                    <a href="{{ route('manager.sellers.show', $row['seller']) }}" class="whitespace-nowrap rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-700">Ajouter/Retirer</a>
                                </div>
                            </div>
                        @empty
                            <p class="py-2 text-gray-500">Aucun vendeur.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="money-amount-row">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Recette du jour</p>
                            <p class="mt-2 break-words text-2xl font-semibold leading-tight text-gray-900" x-text="showToday ? '{{ number_format($stats['total_current_day_revenue'], 0, ',', ' ') }} FCFA' : '******'"></p>
                            <p class="mt-1 text-sm text-gray-500">{{ number_format($stats['today_sales']) }} vente(s)</p>
                        </div>
                        <x-money-eye-button state="showToday" label="la recette du jour" refresh-on-show />
                    </div>
                    <div class="mt-4 max-h-40 overflow-y-auto divide-y divide-gray-100 text-sm">
                        @forelse($sellerFinancials as $row)
                            <div class="flex items-center justify-between py-2">
                                <span class="truncate text-gray-600">{{ $row['seller']->name }}</span>
                                <span class="font-medium text-gray-900" x-text="showToday ? '{{ number_format($row['today_revenue'], 0, ',', ' ') }} FCFA' : '******'"></span>
                            </div>
                        @empty
                            <p class="py-2 text-gray-500">Aucun vendeur.</p>
                        @endforelse
                    </div>
                </div>

            </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Services recents</h3>
                            <span class="text-sm font-medium text-gray-500">Hors ventes</span>
                        </div>
                        <div class="flow-root">
                            <ul role="list" class="-my-4 divide-y divide-gray-200">
                                @forelse($recentServiceOperations as $operation)
                                    <li class="py-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-gray-900">{{ $operation->seller->name ?? 'Vendeur supprime' }}</p>
                                                <p class="text-sm text-gray-500">{{ $operation->reason ?? 'Service MOMO/OM' }}</p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $operation->type === 'add' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                                    {{ $operation->type === 'add' ? 'Depot' : 'Retrait' }}
                                                </span>
                                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ number_format($operation->amount, 0, ',', ' ') }} FCFA</p>
                                                <p class="text-xs text-gray-500">{{ $operation->created_at->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="py-8 text-center text-gray-500">Aucun service recent.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Alertes produits périmés</h3>
                            <a href="{{ route('manager.stock.expiry-alerts') }}" class="text-sm font-medium text-rose-600 hover:text-rose-500">Voir tout</a>
                        </div>
                        <div class="flow-root">
                            <ul role="list" class="-my-5 divide-y divide-gray-200">
                                @forelse($expiringStockMovements as $movement)
                                    @php
                                        $expirationDate = $movement->expiration_date;
                                        $isExpired = $expirationDate?->isPast() && ! $expirationDate?->isToday();
                                        $daysLeft = $expirationDate ? today()->diffInDays($expirationDate, false) : null;
                                    @endphp
                                    <li class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-gray-900">{{ $movement->product->name ?? 'Produit supprime' }}</p>
                                                <p class="text-sm text-gray-500">
                                                    {{ $movement->product->category->name ?? 'Sans categorie' }}
                                                    - Lot {{ $movement->batch_code ?? $movement->id }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $isExpired ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $isExpired ? 'Périmé' : 'J-'.$daysLeft }}
                                                </span>
                                                <p class="mt-1 text-xs text-gray-500">{{ $expirationDate?->format('d/m/Y') }} - {{ $movement->remaining_quantity }} {{ $movement->product->unit ?? '' }}</p>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="py-8 text-center text-gray-500">Aucun produit périmé ou proche de péremption</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Alertes Stock Faible</h3>
                            <a href="{{ route('manager.stock.low-stock') }}" class="text-sm font-medium text-rose-600 hover:text-rose-500">Voir tout</a>
                        </div>
                        <div class="flow-root">
                            <ul role="list" class="-my-5 divide-y divide-gray-200">
                                @forelse($lowStockProducts as $product)
                                    <li class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-gray-900">{{ $product->name }}</p>
                                                <p class="text-sm text-gray-500">{{ $product->category->name }}</p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                                {{ $product->quantity }} {{ $product->unit }}
                                            </span>
                                        </div>
                                    </li>
                                @empty
                                    <li class="py-8 text-center text-gray-500">Aucune alerte de stock</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
