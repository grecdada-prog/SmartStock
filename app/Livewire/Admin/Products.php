<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Validation\Rule;

class Products extends Component
{
    use WithPagination;

    // Propriétés du formulaire
    public $showForm = false;
    public $editingId = null;

    public $name = '';
    public $description = '';
    public $barcode = '';
    public $category_id = '';
    public $purchase_price = '';
    public $selling_price = '';
    public $quantity = '';
    public $min_quantity = '10';
    public $unit = 'pièce';
    public $is_active = true;

    // Recherche et filtre
    public $search = '';
    public $filterCategory = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    /**
     * Règles de validation (dynamiques pour gérer l'édition).
     */
    protected function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'barcode'        => [
                'nullable',
                'string',
                'max:255',
                // ignore l'enregistrement courant en cas d'édition
                Rule::unique('products', 'barcode')->ignore($this->editingId),
            ],
            'category_id'    => ['nullable', 'exists:categories,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price'  => ['required', 'numeric', 'min:0'],
            'quantity'       => ['required', 'integer', 'min:0'],
            'min_quantity'   => ['required', 'integer', 'min:0'],
            'unit'           => ['required', 'string'],
            'is_active'      => ['boolean'],
        ];
    }

    public function render()
    {
        $query = Product::query();

        // Recherche
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('barcode', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Filtre par catégorie
        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        // Tri
        $query->orderBy($this->sortBy, $this->sortDirection);

        $products    = $query->paginate(15);
        $categories  = Category::where('is_active', true)->get();

        return view('livewire.admin.products', [
            'products'   => $products,
            'categories' => $categories,
        ]);
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $this->resetForm();
        $this->showForm   = true;
        $this->editingId  = null;
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit($id)
    {
        $product              = Product::findOrFail($id);
        $this->editingId      = $id;
        $this->name           = $product->name;
        $this->description    = $product->description;
        $this->barcode        = $product->barcode;
        $this->category_id    = $product->category_id;
        $this->purchase_price = $product->purchase_price;
        $this->selling_price  = $product->selling_price;
        $this->quantity       = $product->quantity;
        $this->min_quantity   = $product->min_quantity;
        $this->unit           = $product->unit;
        $this->is_active      = $product->is_active;
        $this->showForm       = true;
    }

    /**
     * Sauvegarder le produit
     */
    public function save()
    {
        // Convertir les valeurs "vides" en null là où c'est pertinent
        $this->barcode     = $this->barcode ?: null;
        $this->category_id = $this->category_id ?: null;

        $this->validate($this->rules());

        if ($this->editingId) {
            $product = Product::findOrFail($this->editingId);

            $product->update([
                'name'           => $this->name,
                'description'    => $this->description,
                'barcode'        => $this->barcode,      // peut être null
                'category_id'    => $this->category_id,  // peut être null
                'purchase_price' => $this->purchase_price,
                'selling_price'  => $this->selling_price,
                'quantity'       => $this->quantity,
                'min_quantity'   => $this->min_quantity,
                'unit'           => $this->unit,
                'is_active'      => $this->is_active,
            ]);

            $this->dispatch('notify', message: 'Produit modifié avec succès !', type: 'success');
        } else {
            Product::create([
                'name'           => $this->name,
                'description'    => $this->description,
                'barcode'        => $this->barcode,      // peut être null
                'category_id'    => $this->category_id,  // peut être null
                'purchase_price' => $this->purchase_price,
                'selling_price'  => $this->selling_price,
                'quantity'       => $this->quantity,
                'min_quantity'   => $this->min_quantity,
                'unit'           => $this->unit,
                'is_active'      => $this->is_active,
            ]);

            $this->dispatch('notify', message: 'Produit créé avec succès !', type: 'success');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Supprimer un produit
     */
    public function delete($id)
    {
        Product::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Produit supprimé avec succès !', type: 'success');
    }

    /**
     * Basculer l'état actif/inactif
     */
    public function toggleActive($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activé' : 'désactivé';
        $this->dispatch('notify', message: "Produit {$status} avec succès !", type: 'success');
    }

    /**
     * Réinitialiser le formulaire
     */
    public function resetForm()
    {
        $this->name           = '';
        $this->description    = '';
        $this->barcode        = '';
        $this->category_id    = '';
        $this->purchase_price = '';
        $this->selling_price  = '';
        $this->quantity       = '';
        $this->min_quantity   = '10';
        $this->unit           = 'pièce';
        $this->is_active      = true;
        $this->editingId      = null;
    }

    /**
     * Annuler l'édition
     */
    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
    }
}
