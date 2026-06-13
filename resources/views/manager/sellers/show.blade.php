@extends('manager.layouts.app')

@section('title', 'Détails du Vendeur')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <div class="flex items-center">
                <div class="h-12 w-12 rounded-full bg-rose-100 flex items-center justify-center relative">
                    <span class="text-rose-600 font-medium text-xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @if(\App\Services\SessionManager::isUserOnline($user->id))
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500 border-2 border-white"></span>
                        </span>
                    @endif
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $user->name }}</h1>
                    <p class="mt-1 text-sm text-gray-700">
                        {{ $user->email }}
                        @if(\App\Services\SessionManager::isUserOnline($user->id))
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">
                                En ligne
                            </span>
                        @else
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                Hors ligne
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
            <a href="{{ route('manager.sellers.edit', $user) }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Modifier
            </a>

            <a href="{{ route('manager.sellers.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour
            </a>
        </div>
    </div>

    <div class="mt-6 overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 sm:rounded-lg" x-data="{ cashType: '{{ old('type', 'withdraw') }}', balanceType: '{{ old('balance_type', 'cash') }}' }">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-4 sm:px-8">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Ajouter / Retirer des fonds</h2>
                    <p class="mt-1 text-sm text-gray-500">Mouvement manuel sur la Caisse Cash ou la Caisse MOMO/OM de ce vendeur.</p>
                </div>
                <span class="mt-2 inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200 sm:mt-0">
                    Action sensible
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 p-5 lg:grid-cols-12 lg:p-8">
            <div class="lg:col-span-5 xl:col-span-4">
                <h3 class="text-base font-semibold text-gray-900">Soldes disponibles</h3>
                <div class="mt-3 grid gap-3">
                    <div class="rounded-md border border-gray-200 bg-white p-4">
                        <p class="text-sm text-gray-500">Caisse Cash</p>
                        <p class="mt-1 break-words text-2xl font-semibold text-gray-900">{{ number_format($stats['cash_balance'], 0, ',', ' ') }} FCFA</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-white p-4">
                        <p class="text-sm text-gray-500">Caisse MOMO/OM</p>
                        <p class="mt-1 break-words text-2xl font-semibold text-gray-900">{{ number_format($stats['mobile_money_balance'], 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>
                <p class="mt-3 max-w-md text-sm leading-6 text-gray-500">Un ajout augmente le solde du vendeur. Un retrait le réduit directement, ainsi que le solde global du gérant et celui du superadmin.</p>

                <div class="mt-6 max-w-md rounded-md border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    <p class="font-semibold">Confirmation requise</p>
                    <p class="mt-1">Saisissez le montant et le motif, puis confirmez l'action. Les retraits sont tracés et visibles par le superadmin.</p>
                </div>
            </div>

            <div class="lg:col-span-7 xl:col-span-8">
                <form method="POST"
                      action="{{ route('manager.sellers.cash-balance', $user) }}"
                      class="space-y-5"
                      x-on:modal-confirmed-cash-movement.window="$el.submit()">
                    @csrf
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="balance_type" class="block text-sm font-medium text-gray-700">Solde concerne</label>
                            <select id="balance_type" name="balance_type" x-model="balanceType" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                                <option value="cash">Caisse Cash</option>
                                <option value="mobile_money">Caisse MOMO/OM</option>
                            </select>
                        </div>

                        <div>
                            <label for="cash_type" class="block text-sm font-medium text-gray-700">Action</label>
                            <select id="cash_type" name="type" x-model="cashType" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                                <option value="withdraw">Retirer des fonds</option>
                                <option value="add">Ajouter des fonds</option>
                            </select>
                        </div>

                        <div>
                            <label for="cash_amount" class="block text-sm font-medium text-gray-700">Montant</label>
                            <input id="cash_amount" name="amount" type="number" min="1" step="1" required value="{{ old('amount') }}" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label for="cash_reason" class="block text-sm font-medium text-gray-700">Motif</label>
                            <input id="cash_reason" name="reason" type="text" maxlength="255" :required="cashType === 'withdraw'" value="{{ old('reason') }}" placeholder="Ex: versement banque, transfert caisse principale..." class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs leading-5 text-gray-600">Tous les mouvements sont enregistrés dans les logs d'activité. Une confirmation est demandée avant validation.</p>
                            <button type="button"
                                    @click="$dispatch('open-modal-cash-movement')"
                                    class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 sm:w-auto">
                                Confirmer
                            </button>
                        </div>
                    </div>
                </form>

                <x-modal-confirm
                    id="cash-movement"
                    title="Confirmer le mouvement de solde"
                    message="Confirmer ce mouvement sur le solde choisi ?"
                    confirmText="Confirmer"
                    cancelText="Annuler"
                    type="warning" />

                <div class="mt-6 border-t border-gray-100 pt-5">
                    <h4 class="text-sm font-semibold text-gray-900">Derniers mouvements de solde</h4>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Type</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Source</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Solde</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Montant</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Motif</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($recentCashAdjustments as $adjustment)
                                    <tr>
                                        <td class="whitespace-nowrap px-3 py-2">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $adjustment->type === 'withdraw' ? 'bg-red-50 text-red-700' : ($adjustment->type === 'correction_cancellation' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                                                {{ $adjustment->type === 'withdraw' ? 'Retrait' : ($adjustment->type === 'correction_cancellation' ? 'Correction annulation' : 'Ajout') }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2 text-gray-600">
                                            {{ $adjustment->source === 'service' ? 'Service' : ($adjustment->source === 'restock' ? 'Appro direct' : 'Manuel') }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2 text-gray-600">
                                            {{ $adjustment->balance_type === 'mobile_money' ? 'Caisse MOMO/OM' : 'Caisse Cash' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2 font-medium text-gray-900">{{ number_format($adjustment->amount, 0, ',', ' ') }} FCFA</td>
                                        <td class="min-w-[12rem] px-3 py-2 text-gray-600">{{ $adjustment->reason ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-gray-500">{{ $adjustment->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">Aucun mouvement de solde enregistre.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Total Ventes -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Ventes</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_sales']) }}</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="font-medium text-blue-600">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA</span>
                    <span class="text-gray-500">de recette totale</span>
                </div>
            </div>
        </div>

        <!-- Ventes Aujourd'hui -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Aujourd'hui</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ number_format($stats['today_sales']) }}</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="font-medium text-rose-600">{{ number_format($stats['today_revenue'], 0, ',', ' ') }} FCFA</span>
                    <span class="text-gray-500">Recette du jour</span>
                </div>
            </div>
        </div>

        <!-- Caisse Cash -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-full min-w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Caisse Cash</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ number_format($stats['cash_balance'], 0, ',', ' ') }} FCFA</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="font-medium text-purple-600">{{ number_format($stats['this_month_sales']) }}</span>
                    <span class="text-gray-500">vente(s) ce mois</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations du vendeur -->
    <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Informations</h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-gray-200">
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Nom complet</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-semibold">{{ $user->name }}</dd>
                </div>

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $user->email }}</dd>
                </div>

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $user->phone ?? 'Non renseigné' }}</dd>
                </div>

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Statut</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        @if($user->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                <svg class="mr-1.5 h-2 w-2 text-rose-400" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Actif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <svg class="mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Inactif
                            </span>
                        @endif
                    </dd>
                </div>

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Créé par</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $user->creator->name ?? 'N/A' }}</dd>
                </div>

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Date de création</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ $user->created_at->format('d/m/Y à H:i') }}
                        <span class="text-gray-500">({{ $user->created_at->diffForHumans() }})</span>
                    </dd>
                </div>

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Dernière activité</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        @if($user->last_activity_at)
                            {{ $user->last_activity_at->format('d/m/Y à H:i') }}
                            <span class="text-gray-500">({{ $user->last_activity_at->diffForHumans() }})</span>
                        @else
                            <span class="text-gray-400">Jamais</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Dernières ventes -->
    <div class="mt-8">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div class="sm:flex-auto">
                <h2 class="text-lg font-semibold text-gray-900">Dernières Ventes</h2>
                <p class="mt-1 text-sm text-gray-700">Les 10 dernières ventes effectuées par ce vendeur</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('manager.sales', ['seller_id' => $user->id]) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition-colors duration-200">
                    Voir toutes les ventes
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="mt-4 flex flex-col">
            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        @if($user->sales->count() > 0)
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Référence</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Montant</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Articles</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($user->sales as $sale)
                                        <tr class="hover:bg-gray-50">
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                #{{ $sale->id }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900">
                                                {{ number_format($sale->total, 0, ',', ' ') }} FCFA
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $sale->items->count() }} article(s)
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $sale->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="bg-white p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune vente</h3>
                                <p class="mt-1 text-sm text-gray-500">Ce vendeur n'a encore effectué aucune vente.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-6">
        <div class="flex items-center space-x-3">
            <form method="POST" action="{{ route('manager.sellers.reset-password', $user) }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500" onclick="return confirm('Envoyer un lien de réinitialisation ?')">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    Réinitialiser mot de passe
                </button>
            </form>

            <form method="POST" action="{{ route('manager.sellers.toggle-status', $user) }}">
                @csrf
                @if($user->is_active)
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Désactiver
                    </button>
                @else
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Activer
                    </button>
                @endif
            </form>

            @if(\App\Services\SessionManager::isUserOnline($user->id))
                <form method="POST" action="{{ route('manager.sellers.force-logout', $user) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500" onclick="return confirm('Déconnecter ce vendeur ?')">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Déconnecter
                    </button>
                </form>
            @endif
        </div>

        <form method="POST" action="{{ route('manager.sellers.destroy', $user) }}" onsubmit="return confirm('Supprimer ce vendeur ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Supprimer
            </button>
        </form>
    </div>
</div>
@endsection
