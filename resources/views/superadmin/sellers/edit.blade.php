@extends('superadmin.layouts.app')

@section('title', 'Modifier un Vendeur')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Modifier le Vendeur
                </h2>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <a href="{{ route('superadmin.sellers.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                    Retour
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="xl:col-span-2">
                <div class="rounded-lg bg-white shadow">
                    <form method="POST" action="{{ route('superadmin.sellers.update', $user) }}" class="space-y-6 p-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nom complet *</label>
                            <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('name') border-red-300 @enderror">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="email" id="email" required value="{{ old('email', $user->email) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('email') border-red-300 @enderror">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Telephone</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('phone') border-red-300 @enderror">
                            @error('phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="manager_id" class="block text-sm font-medium text-gray-700">Gérant responsable *</label>
                            <select name="manager_id" id="manager_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('manager_id') border-red-300 @enderror">
                                <option value="">Sélectionner un gérant</option>
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

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Compte actif
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3 border-t border-gray-200 pt-6">
                            <a href="{{ route('superadmin.sellers.index') }}"
                                class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                Annuler
                            </a>
                            <button type="submit"
                                class="inline-flex justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900">Controle de caisse</h3>
                        <p class="mt-1 text-sm text-gray-500">Pouvoir superadmin pour fermer une caisse a distance. Seul le vendeur peut rouvrir sa caisse.</p>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="rounded-lg bg-gray-50 p-4">
                            <div class="text-sm text-gray-500">Solde Cash actuel</div>
                            <div class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($cashRegisterBalance, 0, ',', ' ') }} FCFA</div>
                            <div class="mt-2 text-sm">
                                @if($pendingCashClosure)
                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">Caisse fermée</span>
                                    <span class="ml-2 text-gray-500">depuis le {{ $pendingCashClosure->closed_at->format('d/m/Y H:i') }}</span>
                                @elseif($openCashRegister)
                                    <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Caisse ouverte</span>
                                    <span class="ml-2 text-gray-500">depuis le {{ $openCashRegister->opened_at->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">Caisse non ouverte</span>
                                @endif
                            </div>
                        </div>

                        @if($pendingCashClosure)
                            <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                La caisse est fermee. Elle ne peut etre rouverte que par le vendeur depuis son dashboard.
                            </div>
                        @elseif($openCashRegister)
                            <form method="POST" action="{{ route('superadmin.sellers.cash-register.close', $user) }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label for="close_reason" class="block text-sm font-medium text-gray-700">Motif de fermeture *</label>
                                    <textarea name="reason" id="close_reason" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm @error('reason') border-red-300 @enderror"
                                        placeholder="Explique pourquoi la caisse doit être fermée...">{{ old('reason') }}</textarea>
                                </div>
                                <button type="submit"
                                    class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    Fermer la caisse
                                </button>
                            </form>
                        @else
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                                La caisse n'est pas ouverte aujourd'hui. Seul le vendeur peut l'ouvrir depuis son dashboard.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900">Reaffecter le vendeur</h3>
                        <p class="mt-1 text-sm text-gray-500">Action superadmin avec motif obligatoire et trace complete.</p>
                    </div>
                    <form method="POST" action="{{ route('superadmin.sellers.reassign-manager', $user) }}" class="space-y-4 p-6">
                        @csrf

                        <div>
                            <label for="reassign_manager_id" class="block text-sm font-medium text-gray-700">Nouveau gérant *</label>
                            <select name="manager_id" id="reassign_manager_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('manager_id') border-red-300 @enderror">
                                <option value="">Sélectionner un gérant</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}" {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                                        {{ $manager->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Gérant actuel: {{ $user->creator->name ?? 'Non défini' }}</p>
                        </div>

                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">Motif de réaffectation *</label>
                            <textarea name="reason" id="reason" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('reason') border-red-300 @enderror"
                                placeholder="Explique pourquoi ce vendeur change de gérant...">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Reaffecter maintenant
                        </button>
                    </form>
                </div>

                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900">Historique recent</h3>
                        <p class="mt-1 text-sm text-gray-500">Dernières réaffectations de ce vendeur.</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @forelse($reassignmentHistory as $entry)
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ data_get($entry->properties, 'previous_manager_name', 'N/A') }}
                                        <span class="mx-1 text-gray-400">-></span>
                                        {{ data_get($entry->properties, 'new_manager_name', 'N/A') }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600">{{ data_get($entry->properties, 'reason', 'Aucun motif') }}</p>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Par {{ $entry->user->name ?? 'Systeme' }} le {{ $entry->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Aucune réaffectation enregistrée pour ce vendeur.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
