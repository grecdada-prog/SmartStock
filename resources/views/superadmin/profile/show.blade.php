<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profil') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <!-- Update Profile Information Form -->
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <div class="md:col-span-1 flex justify-between">
                    <div class="px-4 sm:px-0">
                        <h3 class="text-lg font-medium text-gray-900">Informations du Profil</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Mettez à jour les informations de votre compte et votre adresse e-mail.
                        </p>
                    </div>
                </div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <form method="POST" action="{{ route('account.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="px-4 py-5 bg-white sm:p-6 shadow sm:rounded-tl-md sm:rounded-tr-md">
                            <div class="grid grid-cols-6 gap-6">
                                <!-- Name -->
                                <div class="col-span-6 sm:col-span-4">
                                    <label for="name" class="block font-medium text-sm text-gray-700">Nom</label>
                                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" class="border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm mt-1 block w-full">
                                    @error('name')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Email -->
                                <div class="col-span-6 sm:col-span-4">
                                    <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
                                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm mt-1 block w-full">
                                    @error('email')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Phone -->
                                <div class="col-span-6 sm:col-span-4">
                                    <label for="phone" class="block font-medium text-sm text-gray-700">Téléphone</label>
                                    <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm mt-1 block w-full">
                                    @error('phone')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end px-4 py-3 bg-gray-50 text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <x-section-border />

            <!-- Update Password Form -->
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <div class="md:col-span-1 flex justify-between">
                    <div class="px-4 sm:px-0">
                        <h3 class="text-lg font-medium text-gray-900">Modifier le Mot de Passe</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Assurez-vous que votre compte utilise un mot de passe long et aléatoire pour rester sécurisé.
                        </p>
                    </div>
                </div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <form method="POST" action="{{ route('account.profile.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="px-4 py-5 bg-white sm:p-6 shadow sm:rounded-tl-md sm:rounded-tr-md">
                            <div class="grid grid-cols-6 gap-6">
                                <!-- Current Password -->
                                <div class="col-span-6 sm:col-span-4">
                                    <label for="current_password" class="block font-medium text-sm text-gray-700">Mot de passe actuel</label>
                                    <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm mt-1 block w-full">
                                    @error('current_password')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- New Password -->
                                <div class="col-span-6 sm:col-span-4">
                                    <label for="password" class="block font-medium text-sm text-gray-700">Nouveau mot de passe</label>
                                    <input id="password" type="password" name="password" required autocomplete="new-password" class="border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm mt-1 block w-full">
                                    @error('password')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Confirm Password -->
                                <div class="col-span-6 sm:col-span-4">
                                    <label for="password_confirmation" class="block font-medium text-sm text-gray-700">Confirmer le mot de passe</label>
                                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm mt-1 block w-full">
                                    @error('password_confirmation')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end px-4 py-3 bg-gray-50 text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>