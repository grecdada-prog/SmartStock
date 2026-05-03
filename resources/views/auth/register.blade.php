<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="text-center">
            <h1 class="text-lg font-semibold text-gray-900">Inscription desactivee</h1>
            <p class="mt-2 text-sm text-gray-600">
                Les comptes SmartStock sont crees par un administrateur ou un gerant.
            </p>
            <a href="{{ route('login') }}" class="mt-6 inline-flex items-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700">
                Retour a la connexion
            </a>
        </div>
    </x-authentication-card>
</x-guest-layout>
