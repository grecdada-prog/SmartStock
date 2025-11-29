@extends('manager.layouts.app')

@section('title', 'Gestion des Vendeurs')

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="{ deleteModal: null, autoRefresh: true }" x-init="
    setInterval(() => {
        if (autoRefresh) {
            window.location.reload();
        }
    }, 5000);
">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Mes Vendeurs</h1>
            <p class="mt-2 text-sm text-gray-700">Liste de tous vos vendeurs</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
            <!-- Toggle Auto-refresh -->
            <label class="flex items-center space-x-2 text-sm text-gray-700">
                <input type="checkbox" x-model="autoRefresh" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span>Auto-refresh 5s</span>
            </label>

            <a href="{{ route('manager.sellers.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau Vendeur
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('manager.sellers.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom ou email..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                    <option value="">Tous</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Vendeur</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Contact</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Statut</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ventes</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">CA Total</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date création</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($sellers as $seller)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center relative">
                                                    <span class="text-green-600 font-medium text-lg">{{ strtoupper(substr($seller->name, 0, 1)) }}</span>
                                                    @if(\App\Services\SessionManager::isUserOnline($seller->id))
                                                        <span class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-green-400 border-2 border-white"></span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-medium text-gray-900">{{ $seller->name }}</div>
                                                <div class="text-gray-500">{{ $seller->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $seller->phone ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($seller->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Actif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $seller->sales_count }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ number_format($seller->sales_sum_total ?? 0, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $seller->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-end space-x-3">
                                            <!-- Bouton Modifier -->
                                            <a href="{{ route('manager.sellers.edit', $seller) }}" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                                Modifier
                                            </a>

                                            <!-- Bouton Activer/Désactiver -->
                                            <form method="POST" action="{{ route('manager.sellers.toggle-status', $seller) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-orange-600 hover:text-orange-900" title="{{ $seller->is_active ? 'Désactiver' : 'Activer' }}">
                                                    {{ $seller->is_active ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>

                                            <!-- Bouton Réinitialiser MDP -->
                                            <div x-data="{ resetPasswordId: null }">
                                                <form method="POST"
                                                      action="{{ route('manager.sellers.reset-password', $seller) }}"
                                                      x-on:modal-confirmed-reset-password.window="if(resetPasswordId === {{ $seller->id }}) $el.submit()">
                                                    @csrf
                                                    <button type="button"
                                                            @click="resetPasswordId = {{ $seller->id }}; $dispatch('open-modal-reset-password')"
                                                            class="text-purple-600 hover:text-purple-900"
                                                            title="Réinitialiser mot de passe">
                                                        Reset MDP
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Bouton Déconnecter si en ligne -->
                                            @if(\App\Services\SessionManager::isUserOnline($seller->id))
                                                <form method="POST" action="{{ route('manager.sellers.force-logout', $seller) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Déconnecter">
                                                        Déconnecter
                                                    </button>
                                                </form>
                                            @endif

                                            <!-- Bouton Supprimer -->
                                            <div x-data="{ deleteSellerId: null }">
                                                <form method="POST"
                                                      action="{{ route('manager.sellers.destroy', $seller) }}"
                                                      x-on:modal-confirmed-delete-seller.window="if(deleteSellerId === {{ $seller->id }}) $el.submit()">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            @click="deleteSellerId = {{ $seller->id }}; $dispatch('open-modal-delete-seller')"
                                                            class="text-red-600 hover:text-red-900"
                                                            title="Supprimer">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucun vendeur trouvé.
                                        <a href="{{ route('manager.sellers.create') }}" class="text-green-600 hover:text-green-900 font-medium">Créer votre premier vendeur</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $sellers->links() }}
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<x-modal-confirm
    id="delete-seller"
    title="Supprimer le vendeur"
    message="Êtes-vous sûr de vouloir supprimer ce vendeur ? Cette action est irréversible et supprimera toutes ses données."
    confirmText="Oui, supprimer"
    cancelText="Annuler"
    type="danger" />

<!-- Modal de confirmation reset password -->
<x-modal-confirm
    id="reset-password"
    title="Réinitialiser le mot de passe"
    message="Voulez-vous vraiment réinitialiser le mot de passe de ce vendeur ? Un nouveau mot de passe temporaire sera généré et envoyé par email."
    confirmText="Oui, réinitialiser"
    cancelText="Annuler"
    type="warning" />

@endsection
