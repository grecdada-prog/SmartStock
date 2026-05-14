@extends('manager.layouts.app')

@section('title', 'Gestion des Vendeurs')

@section('content')
<div id="manager-sellers-page" data-silent-refresh class="px-4 sm:px-6 lg:px-8" x-data="{ deleteSellerId: null, resetPasswordId: null, closeCashRegisterId: null }">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Mes Vendeurs</h1>
            <p class="mt-2 text-sm text-gray-700">Liste de tous vos vendeurs</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('manager.sellers.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau Vendeur
            </a>
        </div>
    </div>

    <div class="mt-6 rounded-lg bg-white p-4 shadow">
        <form method="GET" data-auto-filter action="{{ route('manager.sellers.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom ou email..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>
        </form>
    </div>

    <div class="mt-6 rounded-lg bg-white shadow">
        <details class="group">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 border-b border-gray-200 px-4 py-4 sm:px-6">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Suivi des caisses</h2>
                    <p class="mt-1 text-sm text-gray-500">Dernieres fermetures et reouvertures exactes des caisses vendeurs.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">
                        {{ $cashRegisterClosures->count() }} mouvement(s)
                    </span>
                    <svg class="h-5 w-5 text-gray-500 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </div>
            </summary>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Vendeur</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Journee caisse</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Recette fermeture</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Fermeture</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Ouverture</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($cashRegisterClosures as $closure)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $closure->seller->name ?? 'Vendeur supprime' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $closure->business_date->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900">{{ number_format((float) $closure->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="min-w-52 px-4 py-3 text-gray-600">
                                    <div class="font-medium text-gray-900">
                                        {{ $closure->closed_by === 'automatic' ? 'Automatique' : 'Manuelle' }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-gray-500">{{ $closure->closed_at->format('d/m/Y H:i:s') }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">
                                        Initiee par:
                                        @if($closure->closed_by === 'automatic')
                                            Systeme
                                        @else
                                            {{ $closure->closedByUser->name ?? 'Non trace' }}
                                        @endif
                                    </div>
                                </td>
                                <td class="min-w-52 px-4 py-3 text-gray-600">
                                    @if($closure->opened_at)
                                        <div class="font-medium text-gray-900">
                                            {{ $closure->opened_by === 'automatic' ? 'Automatique' : 'Manuelle' }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-gray-500">{{ $closure->opened_at->format('d/m/Y H:i:s') }}</div>
                                        <div class="mt-0.5 text-xs text-gray-500">
                                            Initiee par: {{ $closure->openedByUser->name ?? ($closure->opened_by ? ucfirst(str_replace('_', ' ', $closure->opened_by)) : 'Non trace') }}
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500">Pas encore rouverte</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($closure->opened_at)
                                        <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">Rouverte</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">Fermee</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucune fermeture de caisse enregistree.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    </div>

    <div class="mt-6 flex flex-col">
        <div class="-mx-4 -my-2 overflow-visible sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-visible shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Vendeur</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Solde Cash</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Statut</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ventes</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Recette totale</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date creation</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($sellers as $seller)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="relative flex h-10 w-10 items-center justify-center rounded-full bg-rose-100">
                                                    <span class="text-lg font-medium text-rose-600">{{ strtoupper(substr($seller->name, 0, 1)) }}</span>
                                                    @if(\App\Services\SessionManager::isUserOnline($seller->id))
                                                        <span class="absolute -right-1 -top-1 h-3 w-3 rounded-full border-2 border-white bg-rose-400"></span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-medium text-gray-900">{{ $seller->name }}</div>
                                                <div class="text-gray-500">{{ $seller->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span class="font-semibold text-gray-900">{{ number_format($cashBalances[$seller->id] ?? 0, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($seller->is_active)
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-rose-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                                Actif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                                Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $seller->sales_count }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ number_format($seller->sales_sum_total ?? 0, 0, ',', ' ') }} FCFA</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $seller->created_at->format('d/m/Y') }}</td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <x-dropdown align="right" width="48" contentClasses="p-1 bg-white" dropdownClasses="z-[80]">
                                            <x-slot name="trigger">
                                                <button type="button" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                                    Action
                                                    <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <a href="{{ route('manager.sellers.edit', $seller) }}" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-950 focus:bg-gray-50 focus:outline-none">
                                                    Modifier
                                                </a>

                                                <form method="POST" action="{{ route('manager.sellers.toggle-status', $seller) }}">
                                                    @csrf
                                                    <button type="submit" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-orange-700 transition hover:bg-orange-50 focus:bg-orange-50 focus:outline-none">
                                                        {{ $seller->is_active ? 'Desactiver' : 'Activer' }}
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('manager.sellers.reset-password', $seller) }}" x-on:modal-confirmed-reset-password.window="if(resetPasswordId === {{ $seller->id }}) $el.submit()">
                                                    @csrf
                                                    <button type="button" @click="resetPasswordId = {{ $seller->id }}; $dispatch('open-modal-reset-password')" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-purple-700 transition hover:bg-purple-50 focus:bg-purple-50 focus:outline-none">
                                                        Reset MDP
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('manager.sellers.cash-register.close', $seller) }}" x-on:modal-confirmed-close-cash-register.window="if(closeCashRegisterId === {{ $seller->id }}) $el.submit()">
                                                    @csrf
                                                    <button type="button" @click="closeCashRegisterId = {{ $seller->id }}; $dispatch('open-modal-close-cash-register')" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                                                        Cloturer la caisse
                                                    </button>
                                                </form>

                                                @if(\App\Services\SessionManager::isUserOnline($seller->id))
                                                    <form method="POST" action="{{ route('manager.sellers.force-logout', $seller) }}">
                                                        @csrf
                                                        <button type="submit" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-yellow-700 transition hover:bg-yellow-50 focus:bg-yellow-50 focus:outline-none">
                                                            Deconnecter
                                                        </button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('manager.sellers.destroy', $seller) }}" x-on:modal-confirmed-delete-seller.window="if(deleteSellerId === {{ $seller->id }}) $el.submit()">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" @click="deleteSellerId = {{ $seller->id }}; $dispatch('open-modal-delete-seller')" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-red-700 transition hover:bg-red-50 focus:bg-red-50 focus:outline-none">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            </x-slot>
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucun vendeur trouve.
                                        <a href="{{ route('manager.sellers.create') }}" class="font-medium text-rose-600 hover:text-rose-900">Creer votre premier vendeur</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        {{ $sellers->links() }}
    </div>
</div>

<x-modal-confirm
    id="delete-seller"
    title="Supprimer le vendeur"
    message="Etes-vous sur de vouloir supprimer ce vendeur ? Cette action est irreversible et supprimera toutes ses donnees."
    confirmText="Oui, supprimer"
    cancelText="Annuler"
    type="danger" />

<x-modal-confirm
    id="reset-password"
    title="Reinitialiser le mot de passe"
    message="Voulez-vous vraiment reinitialiser le mot de passe de ce vendeur ? Un nouveau mot de passe temporaire sera genere et envoye par email."
    confirmText="Oui, reinitialiser"
    cancelText="Annuler"
    type="warning" />

<x-modal-confirm
    id="close-cash-register"
    title="Cloturer la caisse"
    message="SmartStock va verifier l etat actuel de la caisse. Si elle est ouverte, la recette du jour sera transferee dans le Solde Cash; si elle est deja fermee, vous recevrez simplement son etat."
    confirmText="Verifier et cloturer"
    cancelText="Annuler"
    type="success" />

@endsection
