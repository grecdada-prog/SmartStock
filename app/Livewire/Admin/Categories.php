<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Category;

class Categories extends Component
{
    public $showForm = false;
    public $editingId = null;
    public $search = '';

    public $name = '';
    public $description = '';
    public $is_active = true;

    protected $rules = [
        'name' => 'required|string|max:255|unique:categories,name',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
    ];

    public function render()
    {
        $query = Category::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%');
        }

        $categories = $query->latest()->get();

        return view('livewire.admin.categories', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $this->editingId = $id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->is_active = $category->is_active;
        $this->showForm = true;
    }

    public function save()
    {
        if ($this->editingId) {
            $this->rules['name'] = 'required|string|max:255|unique:categories,name,' . $this->editingId;
        }

        $this->validate();

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);
            $category->update([
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            $this->dispatch('notify', message: 'Catégorie modifiée avec succès !', type: 'success');
        } else {
            Category::create([
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            $this->dispatch('notify', message: 'Catégorie créée avec succès !', type: 'success');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete($id)
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            $this->dispatch('notify', message: 'Impossible de supprimer une catégorie contenant des produits !', type: 'error');
            return;
        }

        $category->delete();
        $this->dispatch('notify', message: 'Catégorie supprimée avec succès !', type: 'success');
    }

    public function toggleActive($id)
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
        
        $status = $category->is_active ? 'activée' : 'désactivée';
        $this->dispatch('notify', message: "Catégorie {$status} avec succès !", type: 'success');
    }

    public function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
        $this->editingId = null;
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
    }
}