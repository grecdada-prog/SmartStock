<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Gérant') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Produits Total</div>
                    <div class="text-3xl font-bold text-gray-900">0</div>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Ventes du Jour</div>
                    <div class="text-3xl font-bold text-green-600">0 FCFA</div>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Stock Faible</div>
                    <div class="text-3xl font-bold text-orange-600">0</div>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Vendeurs Actifs</div>
                    <div class="text-3xl font-bold text-blue-600">1</div>
                </div>
            </div>

            <!-- Menu principal -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Gestion des Produits -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <h3 class="text-xl font-bold">Produits</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Gérer les produits et catégories</p>
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-semibold">Accéder →</a>
                </div>

                <!-- Gestion des Stocks -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <h3 class="text-xl font-bold">Stocks</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Inventaire et mouvements</p>
                    <a href="#" class="text-green-600 hover:text-green-800 font-semibold">Accéder →</a>
                </div>

                <!-- Approvisionnements -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="text-xl font-bold">Approvisionnements</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Commandes et fournisseurs</p>
                    <a href="#" class="text-purple-600 hover:text-purple-800 font-semibold">Accéder →</a>
                </div>

                <!-- Ventes -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="text-xl font-bold">Ventes</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Historique et rapports</p>
                    <a href="#" class="text-yellow-600 hover:text-yellow-800 font-semibold">Accéder →</a>
                </div>

                <!-- Clients -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="text-xl font-bold">Clients</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Base de données clients</p>
                    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-semibold">Accéder →</a>
                </div>

                <!-- Gestion des Vendeurs -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <h3 class="text-xl font-bold">Vendeurs</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Gérer les comptes vendeurs</p>
                    <a href="#" class="text-red-600 hover:text-red-800 font-semibold">Accéder →</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>