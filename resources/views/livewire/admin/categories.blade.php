<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gestion des Catégories</h1>
                <p class="text-gray-600 mt-1">Total: {{ $categories->count() }} catégories</p>
            </div>
            <button wire:click="create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                + Nouvelle Catégorie
            </button>
        </div>

        <!-- Formulaire -->
        @if ($this->showForm)
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">{{ $this->editingId ? 'Modifier' : 'Créer' }} une catégorie</h2>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            @error('name') border-red-500 @enderror>
                        @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">Catégorie active</label>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" wire:click="cancel" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Annuler
                        </button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            {{ $this->editingId ? 'Modifier' : 'Créer' }} la catégorie
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Recherche -->
        <div class="mb-6">
            <input type="text" wire:model.live="search" placeholder="Chercher une catégorie..." 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Liste des catégories -->
        <div class="space-y-3">
            @forelse ($categories as $category)
                <div class="bg-white rounded-lg shadow p-4 flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-900">{{ $category->name }}</h3>
                        @if ($category->description)
                            <p class="text-gray-600 text-sm mt-1">{{ $category->description }}</p>
                        @endif
                        <p class="text-gray-500 text-xs mt-2">{{ $category->products_count }} produit(s)</p>
                    </div>

                    <div class="flex space-x-2">
                        <button wire:click="toggleActive({{ $category->id }})" class="px-3 py-1 rounded text-xs font-semibold
                            @if ($category->is_active)
                                bg-blue-100 text-blue-800 hover:bg-blue-200
                            @else
                                bg-gray-100 text-gray-800 hover:bg-gray-200
                            @endif">
                            {{ $category->is_active ? 'Actif' : 'Inactif' }}
                        </button>

                        <button wire:click="edit({{ $category->id }})" class="text-blue-600 hover:text-blue-800 font-semibold px-3 py-1">
                            Modifier
                        </button>

                        <button wire:click="delete({{ $category->id }})" wire:confirm="Êtes-vous sûr ?" class="text-red-600 hover:text-red-800 font-semibold px-3 py-1">
                            Supprimer
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg shadow p-6 text-center text-gray-600">
                    Aucune catégorie trouvée
                </div>
            @endforelse
        </div>
    </div>
</div>