<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Liste des catégories créées par le manager authentifié
     */
    public function index(Request $request)
    {
        $superAdminIds = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->pluck('id');

        $query = Category::where(function ($query) use ($superAdminIds) {
                $query->where('created_by', auth()->id())
                    ->orWhereIn('created_by', $superAdminIds);
            })
            ->withCount([
                'products as products_count' => fn ($query) => $query->where('created_by', auth()->id()),
            ]);

        // Filtres
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $categories = $query
            ->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [auth()->id()])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('manager.categories.index', compact('categories'));
    }

    /**
     * Afficher le formulaire de création de catégorie
     */
    public function create()
    {
        return view('manager.categories.create');
    }

    /**
     * Créer une nouvelle catégorie
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        ActivityLog::log(
            'category_created',
            "Catégorie créée : {$category->name}",
            'Category',
            $category->id
        );

        return redirect()->route('manager.categories.index')
            ->with('success', 'Catégorie créée avec succès !');
    }

    /**
     * Afficher les détails d'une catégorie
     */
    public function show(Category $category)
    {
        // Vérifier que la catégorie appartient bien au manager
        $this->authorize('view', $category);

        $category->load(['products' => function($query) {
            $query->latest()->limit(10);
        }, 'creator']);

        return view('manager.categories.show', compact('category'));
    }

    /**
     * Afficher le formulaire d'édition d'une catégorie
     */
    public function edit(Category $category)
    {
        // Vérifier que la catégorie appartient bien au manager
        $this->authorize('update', $category);

        return view('manager.categories.edit', compact('category'));
    }

    /**
     * Mettre à jour une catégorie
     */
    public function update(Request $request, Category $category)
    {
        // Vérifier que la catégorie appartient bien au manager
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $oldName = $category->name;

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? $category->is_active,
        ]);

        $changeDescription = $oldName !== $category->name ? " (ancien nom: {$oldName})" : '';

        ActivityLog::log(
            'category_updated',
            "Catégorie mise à jour : {$category->name}{$changeDescription}",
            'Category',
            $category->id
        );

        return redirect()->route('manager.categories.index')
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    /**
     * Supprimer une catégorie
     */
    public function destroy(Category $category)
    {
        // Vérifier que la catégorie appartient bien au manager
        $this->authorize('delete', $category);

        // Vérifier si la catégorie a des produits
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer cette catégorie car elle contient des produits.');
        }

        $categoryName = $category->name;

        ActivityLog::log(
            'category_deleted',
            "Catégorie supprimée : {$categoryName}",
            'Category',
            $category->id
        );

        $category->delete();

        return redirect()->route('manager.categories.index')
            ->with('success', "Catégorie {$categoryName} supprimée avec succès.");
    }

    /**
     * Activer/désactiver une catégorie
     */
    public function toggleStatus(Category $category)
    {
        // Vérifier que la catégorie appartient bien au manager
        $this->authorize('toggleStatus', $category);

        $newStatus = !$category->is_active;
        $category->update(['is_active' => $newStatus]);

        // Si on désactive la catégorie, désactiver aussi tous ses produits
        if (!$newStatus) {
            $category->products()->update(['is_active' => false]);
        }

        ActivityLog::log(
            'category_status_changed',
            "Statut changé pour {$category->name} : " . ($newStatus ? 'activé' : 'désactivé'),
            'Category',
            $category->id
        );

        return back()->with('success', "Catégorie " . ($newStatus ? 'activée' : 'désactivée') . " avec succès.");
    }
}
