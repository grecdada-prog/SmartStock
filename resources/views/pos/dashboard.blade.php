<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Point de Vente') }} - {{ auth()->user()->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistiques du vendeur -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Mes Ventes Aujourd'hui</div>
                    <div class="text-3xl font-bold text-green-600">0 FCFA</div>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Nombre de Transactions</div>
                    <div class="text-3xl font-bold text-blue-600">0</div>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-gray-500 text-sm">Produits Disponibles</div>
                    <div class="text-3xl font-bold text-gray-900">0</div>
                </div>
            </div>

            <!-- Menu principal vendeur -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nouvelle Vente -->
                <div class="bg-gradient-to-br from-green-500 to-green-700 overflow-hidden shadow-xl sm:rounded-lg p-8 hover:shadow-2xl transition text-white">
                    <div class="flex items-center mb-4">
                        <svg class="w-12 h-12 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <h3 class="text-2xl font-bold">Nouvelle Vente</h3>
                    </div>
                    <p class="mb-6 text-green-100">Créer une nouvelle facture</p>
                    <a href="#" class="inline-block bg-white text-green-700 px-6 py-3 rounded-lg font-bold hover:bg-green-50 transition">
                        Démarrer →
                    </a>
                </div>

                <!-- Consulter Stock -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-10 h-10 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <h3 class="text-2xl font-bold">Consulter Stock</h3>
                    </div>
                    <p class="text-gray-600 mb-6">Vérifier la disponibilité des produits</p>
                    <a href="#" class="inline-block text-blue-600 hover:text-blue-800 font-bold text-lg">
                        Voir le stock →
                    </a>
                </div>

                <!-- Mes Ventes -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-10 h-10 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-2xl font-bold">Mes Ventes</h3>
                    </div>
                    <p class="text-gray-600 mb-6">Historique de mes transactions</p>
                    <a href="#" class="inline-block text-purple-600 hover:text-purple-800 font-bold text-lg">
                        Voir l'historique →
                    </a>
                </div>

                <!-- Clients -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 hover:shadow-2xl transition">
                    <div class="flex items-center mb-4">
                        <svg class="w-10 h-10 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="text-2xl font-bold">Clients</h3>
                    </div>
                    <p class="text-gray-600 mb-6">Gérer les informations clients</p>
                    <a href="#" class="inline-block text-indigo-600 hover:text-indigo-800 font-bold text-lg">
                        Voir les clients →
                    </a>
                </div>
            </div>

            <!-- Info box -->
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-500 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-blue-900 font-semibold">Accès vendeur</p>
                        <p class="text-blue-700 text-sm">Vous pouvez créer des ventes et consulter les stocks. Pour modifier les produits ou les prix, contactez le gérant.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>