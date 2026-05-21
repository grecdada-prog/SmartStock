@extends('superadmin.layouts.app')

@section('title', 'Créer un Utilisateur')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Créer un Nouvel Utilisateur
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('superadmin.users.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                    Retour
                </a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg" x-data="{ selectedRole: @js(old('role', '')) }">
            <form method="POST" action="{{ route('superadmin.users.store') }}" class="space-y-6 p-6">
                @csrf

                <!-- Nom -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nom complet *</label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

<!-- Champ Email -->
<div>
    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
    <input type="email" 
           name="email" 
           id="email" 
           value="{{ old('email') }}" 
           required 
           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('email') border-red-500 @enderror">
    @error('email')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<!-- Champ Téléphone -->
<div>
    <label for="phone" class="block text-sm font-medium text-gray-700">Téléphone</label>
    <input type="tel" 
           name="phone" 
           id="phone" 
           value="{{ old('phone') }}" 
           pattern="[0-9]{9,15}"
           placeholder="Ex: 690123456"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('phone') border-red-500 @enderror"
           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
    <p class="mt-1 text-xs text-gray-500">Uniquement des chiffres (9-15 caractères)</p>
    @error('phone')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

                <!-- Rôle -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Rôle *</label>
                    <select name="role" id="role" required x-model="selectedRole"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('role') border-red-300 @enderror">
                        <option value="">Sélectionner un rôle</option>
                        <option value="super_admin" {{ old('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Gérant</option>
                        <option value="seller" {{ old('role') == 'seller' ? 'selected' : '' }}>Vendeur</option>
                    </select>
                    @error('role')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="selectedRole === 'seller'" x-cloak>
                    <label for="manager_id" class="block text-sm font-medium text-gray-700">Gérant responsable *</label>
                    <select name="manager_id" id="manager_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('manager_id') border-red-300 @enderror">
                        <option value="">Sélectionner un gérant</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                                {{ $manager->name }} - {{ $manager->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('manager_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Un vendeur doit être rattaché à un gérant actif.</p>
                </div>

                <!-- Mot de passe -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe *</label>
                    <input type="password" name="password" id="password" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('password') border-red-300 @enderror">
                    <p class="mt-1 text-sm text-gray-500">Minimum 8 caractères avec majuscules, minuscules, chiffres et symboles</p>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirmation mot de passe -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmer le mot de passe *</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                </div>

                <!-- Statut -->
                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Compte actif</label>
                </div>

                <!-- Boutons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('superadmin.users.index') }}"
                        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                        Annuler
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                        Créer l'utilisateur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

