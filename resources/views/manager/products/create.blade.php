@extends('manager.layouts.app')

@section('title', 'Créer un produit')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Créer un nouveau produit</h1>
            <a href="{{ route('manager.products.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Retour</a>
        </div>

        <div class="rounded-lg bg-white shadow">
            <form method="POST" action="{{ route('manager.products.store') }}" class="space-y-5 p-6">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom du produit *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="barcode" class="block text-sm font-medium text-gray-700">Code-barres</label>
                        <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}" placeholder="11012035024090" pattern="\d+" inputmode="numeric" autocomplete="off" data-barcode-format class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm @error('barcode') border-red-300 @enderror">
                        @error('barcode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Categorie *</label>
                        <select name="category_id" id="category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                            <option value="">Sélectionner</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="alert_quantity" class="block text-sm font-medium text-gray-700">Seuil d'alerte *</label>
                        <input type="number" name="alert_quantity" id="alert_quantity" required value="{{ old('alert_quantity', 10) }}" min="0" step="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        @error('alert_quantity')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700">Unite *</label>
                        <input type="text" name="unit" id="unit" required value="{{ old('unit', 'piece') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm">
                        @error('unit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2 flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">Produit actif</label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
                    <a href="{{ route('manager.products.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annuler</a>
                    <button type="submit" class="rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Créer le produit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
