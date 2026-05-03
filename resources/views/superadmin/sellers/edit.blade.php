@extends('superadmin.layouts.app')

@section('title', 'Modifier un Vendeur')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Modifier le Vendeur
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('superadmin.sellers.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Retour
                </a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('superadmin.sellers.update', $user) }}" class="space-y-6 p-6">
                @csrf
                @method('PUT')

                <!-- Nom -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nom complet *</label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                    <input type="email" name="email" id="email" required value="{{ old('email', $user->email) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('email') border-red-300 @enderror">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Téléphone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Téléphone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('phone') border-red-300 @enderror">
                    @error('phone')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Gerant responsable -->
                <div>
                    <label for="manager_id" class="block text-sm font-medium text-gray-700">Gerant responsable *</label>
                    <select name="manager_id" id="manager_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('manager_id') border-red-300 @enderror">
                        <option value="">Selectionner un gerant</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ old('manager_id', $user->created_by) == $manager->id ? 'selected' : '' }}>
                                {{ $manager->name }} - {{ $manager->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('manager_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Statut actif -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Compte actif
                    </label>
                </div>

                <!-- Boutons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('superadmin.sellers.index') }}"
                        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Annuler
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
