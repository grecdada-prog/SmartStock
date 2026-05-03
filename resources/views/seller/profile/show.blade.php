@extends('seller.layouts.app')

@section('title', 'Mon Profil')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl">
                    Mon Profil
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Gere vos informations personnelles de vendeur.
                </p>
            </div>
        </div>

        <div class="mb-6 rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">
                    Informations personnelles
                </h3>

                <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom complet</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            required
                            value="{{ old('name', $user->name) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('name') border-red-300 @enderror"
                        >
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Adresse email</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            required
                            value="{{ old('email', $user->email) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('email') border-red-300 @enderror"
                        >
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Telephone</label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone', $user->phone) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('phone') border-red-300 @enderror"
                        >
                        @error('phone')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <div class="mt-1">
                            <span class="inline-flex items-center rounded-md bg-green-100 px-3 py-2 text-sm font-medium text-green-800">
                                Vendeur
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Membre depuis</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('d/m/Y') }}</p>
                    </div>

                    <div class="flex justify-end border-t border-gray-200 pt-4">
                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
