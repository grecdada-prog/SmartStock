@extends('superadmin.layouts.app')

@section('title', 'Gestion des Utilisateurs')

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="{ deleteModal: null }">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Tous les Utilisateurs</h1>
            <p class="mt-2 text-sm text-gray-700">Liste complète de tous les utilisateurs du système</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
            <x-export-buttons
                :excelRoute="route('superadmin.users.export.excel', request()->query())"
                :pdfRoute="route('superadmin.users.export.pdf', request()->query())" />
            <a href="{{ route('superadmin.users.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvel Utilisateur
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('superadmin.users.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom ou email..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Rôle</label>
                <select name="role" id="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                    <option value="">Tous les rôles</option>
                    <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="manager" {{ request('role') == 'manager' ? 'selected' : '' }}>Gérant</option>
                    <option value="seller" {{ request('role') == 'seller' ? 'selected' : '' }}>Vendeur</option>
                </select>
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

    <!-- Table -->
    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-visible sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-visible shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Utilisateur</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Rôle</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Statut</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Créé par</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date création</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($users as $user)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-rose-100 flex items-center justify-center">
                                                    <span class="text-rose-600 font-medium text-lg">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                                <div class="text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                            {{ $user->hasRole('super_admin') ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $user->hasRole('manager') ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $user->hasRole('seller') ? 'bg-rose-100 text-rose-800' : '' }}">
                                            @if($user->hasRole('super_admin'))
                                                Super Admin
                                            @elseif($user->hasRole('manager'))
                                                Gérant
                                            @elseif($user->hasRole('seller'))
                                                Vendeur
                                            @endif
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($user->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-rose-400" fill="currentColor" viewBox="0 0 8 8">
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
                                        {{ $user->creator->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $user->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <x-action-menu>
                                            <a href="{{ route('superadmin.users.edit', $user) }}" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-blue-700 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none">
                                                Modifier
                                            </a>

                                            @if($user->id !== auth()->id())
                                                <form method="POST" action="{{ route('superadmin.users.toggle-status', $user) }}">
                                                    @csrf
                                                    <button type="submit" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-orange-700 transition hover:bg-orange-50 focus:bg-orange-50 focus:outline-none">
                                                        @if($user->is_active)
                                                            Désactiver
                                                        @else
                                                            Activer
                                                        @endif
                                                    </button>
                                                </form>

                                                <div x-data="{ resetPasswordId: null }">
                                                    <form method="POST"
                                                          action="{{ route('superadmin.users.reset-password', $user) }}"
                                                          x-on:modal-confirmed-reset-password.window="if(resetPasswordId === {{ $user->id }}) $el.submit()">
                                                        @csrf
                                                        <button type="button"
                                                                @click="resetPasswordId = {{ $user->id }}; $dispatch('open-modal-reset-password')"
                                                                class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-purple-700 transition hover:bg-purple-50 focus:bg-purple-50 focus:outline-none">
                                                            Réinitialiser MDP
                                                        </button>
                                                    </form>
                                                </div>

                                                <button type="button"
                                                        @click="deleteModal = {{ $user->id }}"
                                                        class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-red-700 transition hover:bg-red-50 focus:bg-red-50 focus:outline-none">
                                                    Supprimer
                                                </button>
                                            @endif
                                        </x-action-menu>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucun utilisateur trouvé
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
        {{ $users->links() }}
    </div>

    <!-- Modales de confirmation de suppression (inline pour supporter le nom dynamique) -->
    @foreach($users as $user)
        @if($user->id !== auth()->id())
            <div x-show="deleteModal === {{ $user->id }}"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 style="display: none;">

                <!-- Overlay -->
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="deleteModal = null"></div>

                <!-- Modal -->
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div @click.away="deleteModal = null"
                         class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full"
                         x-show="deleteModal === {{ $user->id }}"
                         x-transition>

                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        Supprimer l'utilisateur
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Supprimer <strong>{{ $user->name }}</strong> ? Cette action est definitive.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    Supprimer
                                </button>
                            </form>
                            <button type="button"
                                    @click="deleteModal = null"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

<!-- Modal de confirmation reset password -->
<x-modal-confirm
    id="reset-password"
    title="Réinitialiser le mot de passe"
    message="Envoyer un lien de réinitialisation à cet utilisateur ?"
    confirmText="Réinitialiser"
    cancelText="Annuler"
    type="warning" />

@endsection
