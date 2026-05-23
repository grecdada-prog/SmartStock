<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Liste des produits créés par le manager authentifié
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'creator'])
            ->where('created_by', auth()->id())
            ->withCount('saleItems');

        // Filtres
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'low':
                    $query->lowStock();
                    break;
                case 'out':
                    $query->where('quantity', '<=', 0);
                    break;
                case 'in':
                    $query->inStock();
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $barcodeSearch = preg_replace('/\D+/', '', $search);

            $query->where(function($q) use ($search, $barcodeSearch) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('barcode', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');

                if ($barcodeSearch !== '') {
                    $q->orWhereRaw("REPLACE(barcode, ' ', '') like ?", ['%' . $barcodeSearch . '%']);
                }
            });
        }

        $products = $query->latest()->get();

        // Catégories pour le filtre
        $categories = $this->availableCategoriesQuery()
            ->active()
            ->orderBy('name')
            ->get();

        return view('manager.products.index', compact('products', 'categories'));
    }

    /**
     * Afficher le formulaire de création de produit
     */
    public function create()
    {
        $categories = $this->availableCategoriesQuery()
            ->active()
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            return redirect()->route('manager.categories.create')
                ->with('error', 'Vous devez créer au moins une catégorie avant de créer un produit.');
        }

        return view('manager.products.create', compact('categories'));
    }

    /**
     * Créer un nouveau produit
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'regex:/^\d+$/', 'unique:products,barcode'],
            'category_id' => ['required', 'exists:categories,id'],
            'alert_quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ], [
            'sku.unique' => 'Ce code SKU existe déjà.',
            'barcode.regex' => 'Le code-barres doit contenir uniquement des chiffres.',
            'barcode.unique' => 'Ce code-barres existe deja pour un autre produit.',
            'selling_price.gte' => 'Le prix de vente doit être supérieur ou égal au prix d\'achat.',
        ]);

        // Vérifier que la catégorie est disponible pour le manager.
        $category = $this->availableCategoriesQuery()
            ->where('id', $validated['category_id'])
            ->first();

        if (!$category) {
            return back()->withErrors(['category_id' => 'Catégorie invalide.'])->withInput();
        }

        $product = Product::create([
            'name' => $validated['name'],
            'sku' => $this->generateSku($validated['name']),
            'barcode' => ($validated['barcode'] ?? null) ?: null,
            'description' => null,
            'category_id' => $validated['category_id'],
            'purchase_price' => 0,
            'selling_price' => 0,
            'quantity' => 0,
            'alert_quantity' => $validated['alert_quantity'],
            'unit' => $validated['unit'],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        ActivityLog::log(
            'product_created',
            "Produit créé : {$product->name} (SKU: {$product->sku})",
            'Product',
            $product->id
        );

        return redirect()->route('manager.products.index')
            ->with('success', 'Produit enregistre avec succes !');
    }

    /**
     * Afficher les détails d'un produit
     */
    public function show(Product $product)
    {
        // Vérifier que le produit appartient bien au manager
        $this->authorize('view', $product);

        $product->load(['category', 'creator', 'stockMovements' => function($query) {
            $query->latest()->limit(10);
        }, 'saleItems.sale']);

        return view('manager.products.show', compact('product'));
    }

    /**
     * Afficher le formulaire d'édition d'un produit
     */
    public function edit(Product $product)
    {
        // Vérifier que le produit appartient bien au manager
        $this->authorize('update', $product);

        $categories = $this->availableCategoriesQuery()
            ->active()
            ->orderBy('name')
            ->get();

        return view('manager.products.edit', compact('product', 'categories'));
    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, Product $product)
    {
        // Vérifier que le produit appartient bien au manager
        $this->authorize('update', $product);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'exists:categories,id'],
            'alert_quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ], [
            'selling_price.gte' => 'Le prix de vente doit être supérieur ou égal au prix d\'achat.',
        ]);

        // Vérifier que la catégorie est disponible pour le manager.
        $category = $this->availableCategoriesQuery()
            ->where('id', $validated['category_id'])
            ->first();

        if (!$category) {
            return back()->withErrors(['category_id' => 'Catégorie invalide.'])->withInput();
        }

        $oldValues = $product->only(['name', 'sku']);

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'alert_quantity' => $validated['alert_quantity'],
            'unit' => $validated['unit'],
            'is_active' => $validated['is_active'] ?? $product->is_active,
        ]);

        // Log les changements importants
        $changes = [];
        if ($oldValues['name'] !== $product->name) {
            $changes[] = "nom: {$oldValues['name']} → {$product->name}";
        }
        $changeDescription = empty($changes) ? '' : ' (' . implode(', ', $changes) . ')';

        ActivityLog::log(
            'product_updated',
            "Produit mis à jour : {$product->name}{$changeDescription}",
            'Product',
            $product->id
        );

        return redirect()->route('manager.products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    /**
     * Supprimer un produit (soft delete)
     */
    public function destroy(Product $product)
    {
        // Vérifier que le produit appartient bien au manager
        $this->authorize('delete', $product);

        // Vérifier si le produit a des ventes
        if ($product->saleItems()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer ce produit car il a des ventes associées.');
        }

        $productName = $product->name;
        $productSku = $product->sku;

        ActivityLog::log(
            'product_deleted',
            "Produit supprimé : {$productName} (SKU: {$productSku})",
            'Product',
            $product->id
        );

        $product->delete();

        return redirect()->route('manager.products.index')
            ->with('success', "Produit {$productName} supprimé avec succès.");
    }

    /**
     * Activer/désactiver un produit
     */
    public function toggleStatus(Product $product)
    {
        // Vérifier que le produit appartient bien au manager
        $this->authorize('toggleStatus', $product);

        $newStatus = !$product->is_active;
        $product->update(['is_active' => $newStatus]);

        ActivityLog::log(
            'product_status_changed',
            "Statut changé pour {$product->name} : " . ($newStatus ? 'activé' : 'désactivé'),
            'Product',
            $product->id
        );

        return back()->with('success', "Produit " . ($newStatus ? 'activé' : 'désactivé') . " avec succès.");
    }

    /**
     * Afficher les produits en stock faible
     */
    public function lowStock()
    {
        $products = Product::where('created_by', auth()->id())
            ->with(['category', 'creator'])
            ->lowStock()
            ->active()
            ->orderBy('quantity', 'asc')
            ->get();

        return view('manager.products.low-stock', compact('products'));
    }

    private function availableCategoriesQuery()
    {
        $superAdminIds = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->pluck('id');

        return Category::where(function ($query) use ($superAdminIds) {
            $query->where('created_by', auth()->id())
                ->orWhereIn('created_by', $superAdminIds);
        });
    }

    private function generateSku(string $productName): string
    {
        $prefix = str($productName)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '')
            ->substr(0, 3)
            ->padRight(3, 'X')
            ->toString();

        do {
            $sku = $prefix.'-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

}

