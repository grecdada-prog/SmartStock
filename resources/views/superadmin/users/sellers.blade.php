@extends('superadmin.layouts.app')

@section('title', 'Gestion des Vendeurs')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Vendeurs</h1>
            <p class="mt-2 text-sm text-gray-700">Liste de tous les vendeurs du système</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('superadmin.sellers.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Créé par</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ventes</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">CA Total</th>
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
                                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                    <span class="text-green-600 font-medium text-lg">{{ strtoupper(substr($seller->name, 0, 1)) }}</span>
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
                                        {{ $seller->creator->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $seller->sales_count }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ number_format($seller->sales_sum_total ?? 0, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('superadmin.users.edit', $seller) }}" class="text-blue-600 hover:text-blue-900">
                                                Modifier
                                            </a>
                                            @if($seller->id !== auth()->id())
                                                <form method="POST" action="{{ route('superadmin.users.toggle-status', $seller) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                        {{ $seller->is_active ? 'Désactiver' : 'Activer' }}
                                                    </button>
                                                </form>
                                                @if(\App\Services\SessionManager::isUserOnline($seller->id))
                                                    <form method="POST" action="{{ route('superadmin.users.force-logout', $seller) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-orange-600 hover:text-orange-900">
                                                            Déconnecter
                                                        </button>
                                                    </form>
                                                @endif
                                                <div x-data="{ deleteSellerId: null }">
                                                    <form method="POST"
                                                          action="{{ route('superadmin.users.destroy', $seller) }}"
                                                          x-on:modal-confirmed-delete-seller-user.window="if(deleteSellerId === {{ $seller->id }}) $el.submit()">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                                @click="deleteSellerId = {{ $seller->id }}; $dispatch('open-modal-delete-seller-user')"
                                                                class="text-red-600 hover:text-red-900">
                                                            Supprimer
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucun vendeur trouvé
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
    id="delete-seller-user"
    title="Supprimer le vendeur"
    message="Êtes-vous sûr de vouloir supprimer ce vendeur ? Cette action est irréversible et supprimera toutes ses données."
    confirmText="Oui, supprimer"
    cancelText="Annuler"
    type="danger" />

@endsection