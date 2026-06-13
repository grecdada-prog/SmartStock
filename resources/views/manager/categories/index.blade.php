@extends('manager.layouts.app')

@section('title', 'Gestion des Catégories')

@section('content')
<div id="manager-categories-page" data-silent-refresh class="px-4 sm:px-6 lg:px-8">
    <div class="smartstore-sticky-zone -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="smartstore-sticky-inner">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Mes Catégories</h1>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 flex items-center space-x-3">
<a href="{{ route('manager.categories.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto transition-colors duration-200">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle Catégorie
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white shadow rounded-lg p-4">
        <form method="GET" data-auto-filter action="{{ route('manager.categories.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom, description..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
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
        </div>
    </div>

    <!-- Table -->
    <div class="mt-6 flex flex-col">
        <div class="-my-2 -mx-4 overflow-visible sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-[980px] table-fixed divide-y divide-gray-300">
                        <colgroup>
                            <col class="w-[250px]">
                            <col>
                            <col class="w-[130px]">
                            <col class="w-[120px]">
                            <col class="w-[130px]">
                        </colgroup>
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Catégorie</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Description</th>
                                <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Produits</th>
                                <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Statut</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 text-center sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($categories as $category)
                                @php($isManagerCategory = (int) $category->created_by === (int) auth()->id())
                                <tr>
                                    <td class="py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-rose-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4 min-w-0">
                                                <div class="line-clamp-2 font-medium leading-5 text-gray-900">{{ $category->name }}</div>
                                                <div class="text-gray-500 text-xs">Créée le {{ $category->created_at->format('d/m/Y') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500">
                                        <div class="truncate">
                                            {{ $category->description ?? 'Aucune description' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-center text-sm text-gray-500">
                                        <span class="inline-flex min-w-[92px] items-center justify-center whitespace-nowrap rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                            {{ $category->products_count }} produit(s)
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 text-center text-sm">
                                        @if($category->is_active)
                                            <span class="inline-flex min-w-[74px] items-center justify-center whitespace-nowrap rounded-full bg-rose-100 px-3 py-1 text-xs font-medium text-rose-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-rose-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Actif
                                            </span>
                                        @else
                                            <span class="inline-flex min-w-[74px] items-center justify-center whitespace-nowrap rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">
                                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3" />
                                                </svg>
                                                Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="relative py-4 pl-3 pr-4 text-center text-sm font-medium sm:pr-6">
                                        @if($isManagerCategory)
                                        <x-action-menu>
                                            <a href="{{ route('manager.categories.edit', $category) }}" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-blue-700 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none">
                                                Modifier
                                            </a>
                                            <form method="POST" action="{{ route('manager.categories.toggle-status', $category) }}">
                                                @csrf
                                                <button type="submit" class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-orange-700 transition hover:bg-orange-50 focus:bg-orange-50 focus:outline-none">
                                                    {{ $category->is_active ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>
                                            <div x-data="{ deleteCategoryId: null }">
                                                <form method="POST"
                                                      action="{{ route('manager.categories.destroy', $category) }}"
                                                      x-on:modal-confirmed-delete-category.window="if(deleteCategoryId === {{ $category->id }}) $el.submit()">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            @click="deleteCategoryId = {{ $category->id }}; $dispatch('open-modal-delete-category')"
                                                            class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm font-medium text-red-700 transition hover:bg-red-50 focus:bg-red-50 focus:outline-none">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </x-action-menu>
                                        @else
                                            <span class="inline-flex min-w-[88px] items-center justify-center whitespace-nowrap rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                                Systeme
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Aucune catégorie trouvée.
                                        <a href="{{ route('manager.categories.create') }}" class="text-rose-600 hover:text-rose-900 font-medium">Créer votre première catégorie</a>
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
        {{ $categories->links() }}
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<x-modal-confirm
    id="delete-category"
    title="Supprimer la catégorie"
    message="Supprimer cette catégorie ?"
    confirmText="Supprimer"
    cancelText="Annuler"
    type="danger" />

@endsection
