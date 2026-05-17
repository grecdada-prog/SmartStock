@extends('superadmin.layouts.app')

@section('title', 'Gestion des Vendeurs')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Vendeurs</h1>
            <p class="mt-2 text-sm text-gray-700">Liste globale des vendeurs et de leur activité</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <a href="{{ route('superadmin.sellers.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau Vendeur
            </a>
        </div>
    </div>

    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('superadmin.sellers.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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

    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-visible sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-visible shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Vendeur</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Contact</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Statut</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Créé par</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ventes</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Recette cumulée</th>
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
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-100">
                                                    <span class="text-lg font-medium text-rose-600">{{ strtoupper(substr($seller->name, 0, 1)) }}</span>
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
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">Actif</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Inactif</span>
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
                                        <x-action-menu>
                                            <a href="{{ route('superadmin.sellers.edit', $seller) }}" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-blue-700 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none">
                                                Modifier
                                            </a>
                                            @if($seller->id !== auth()->id())
                                                <div x-data="{ deleteSellerId: null }">
                                                    <form method="POST"
                                                          action="{{ route('superadmin.sellers.destroy', $seller) }}"
                                                          x-on:modal-confirmed-delete-seller.window="if(deleteSellerId === {{ $seller->id }}) $el.submit()">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                                @click="deleteSellerId = {{ $seller->id }}; $dispatch('open-modal-delete-seller')"
                                                                class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-red-700 transition hover:bg-red-50 focus:bg-red-50 focus:outline-none">
                                                            Supprimer
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </x-action-menu>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucun vendeur trouve
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
    message="Supprimer ce vendeur ? Cette action est definitive."
    confirmText="Supprimer"
    cancelText="Annuler"
    type="danger" />
@endsection
