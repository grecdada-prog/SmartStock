{{-- Because she competes with no one, no one can compete with her. --}}

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gestion des Produits</h1>
                <p class="text-gray-600 mt-1">Total: {{ $products->total() }} produits</p>
            </div>
            <button wire:click="create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                + Nouveau Produit
            </button>
        </div>

        <!-- Formulaire de produit -->
        @if ($this->showForm)
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">{{ $this->editingId ? 'Modifier' : 'Créer' }} un produit</h2>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nom -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                            <input
                                type="text"
                                wire:model="name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                            >
                            @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                        </div>

                        <!-- Code-barres -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Code-barres</label>
                            <input
                                type="text"
                                wire:model="barcode"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('barcode') border-red-500 @enderror"
                            >
                            @error('barcode') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                        </div>

                        <!-- Catégorie -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                            <select
                                wire:model="category_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">-- Sélectionner une catégorie --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Unité -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unité</label>
                            <select
                                wire:model="unit"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="pièce">Pièce</option>
                                <option value="kg">Kilogramme</option>
                                <option value="litre">Litre</option>
                                <option value="mètre">Mètre</option>
                                <option value="boîte">Boîte</option>
                            </select>
                        </div>

                        <!-- Prix d'achat -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat (FCFA) *</label>
                            <input
                                type="number"
                                step="0.01"
                                wire:model="purchase_price"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('purchase_price') border-red-500 @enderror"
                            >
                            @error('purchase_price') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                        </div>

                        <!-- Prix de vente -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix de vente (FCFA) *</label>
                            <input
                                type="number"
                                step="0.01"
                                wire:model="selling_price"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('selling_price') border-red-500 @enderror"
                            >
                            @error('selling_price') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                        </div>

                        <!-- Quantité -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantité en stock *</label>
                            <input
                                type="number"
                                wire:model="quantity"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('quantity') border-red-500 @enderror"
                            >
                            @error('quantity') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                        </div>

                        <!-- Stock minimum -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock minimum *</label>
                            <input
                                type="number"
                                wire:model="min_quantity"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('min_quantity') border-red-500 @enderror"
                            >
                            @error('min_quantity') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            wire:model="description"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                    </div>

                    <!-- Statut -->
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            wire:model="is_active"
                            id="is_active"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">Produit actif</label>
                    </div>

                    <!-- Boutons -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button
                            type="button"
                            wire:click="cancel"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            {{ $this->editingId ? 'Modifier' : 'Créer' }} le produit
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Filtres et recherche -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Recherche -->
                <div>
                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Chercher un produit..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <!-- Filtre catégorie -->
                <div>
                    <select
                        wire:model.live="filterCategory"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">Toutes les catégories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tri -->
                <div>
                    <select
                        wire:model.live="sortBy"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="name">Nom (A-Z)</option>
                        <option value="selling_price">Prix de vente</option>
                        <option value="quantity">Quantité</option>
                        <option value="created_at">Date d'ajout</option>
                    </select>
                </div>

                <!-- Direction du tri -->
                <div>
                    <select
                        wire:model.live="sortDirection"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="asc">Croissant ↑</option>
                        <option value="desc">Décroissant ↓</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tableau des produits -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Catégorie</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Code-barres</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Prix vente</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Stock</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                {{ $product->name }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ $product->category?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ $product->barcode ?? '-' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-right text-gray-900 font-semibold">
                                {{ number_format($product->selling_price, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    @if ($product->quantity <= 0)
                                        bg-red-100 text-red-800
                                    @elseif ($product->isLowStock())
                                        bg-orange-100 text-orange-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif">
                                    {{ $product->quantity }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-center">
                                <button
                                    wire:click="toggleActive({{ $product->id }})"
                                    class="px-2 py-1 rounded text-xs font-semibold
                                        @if ($product->is_active)
                                            bg-blue-100 text-blue-800
                                        @else
                                            bg-gray-100 text-gray-800
                                        @endif"
                                >
                                    {{ $product->is_active ? 'Actif' : 'Inactif' }}
                                </button>
                            </td>
                            <td class="px-6 py-3 text-sm text-right space-x-2">
                                <button
                                    wire:click="edit({{ $product->id }})"
                                    class="text-blue-600 hover:text-blue-800 font-semibold"
                                >
                                    Modifier
                                </button>
                                <button
                                    wire:click="delete({{ $product->id }})"
                                    wire:confirm="Êtes-vous sûr ?"
                                    class="text-red-600 hover:text-red-800 font-semibold"
                                >
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-600">
                                Aucun produit trouvé
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
</div>
