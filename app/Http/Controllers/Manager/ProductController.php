<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;

class ProductController extends Controller
{
    public function search(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $barcodeSearch = preg_replace('/\D+/', '', $search);

        if (mb_strlen($search) < 2 && strlen($barcodeSearch) < 3) {
            return response()->json(['products' => []]);
        }

        $products = Product::with([
            'category',
            'promotions' => fn ($query) => $query
                ->where('manager_id', auth()->id())
                ->orderBy('min_quantity')
                ->orderBy('created_at')
                ->orderBy('id'),
            'stockMovements' => fn ($query) => $query
                ->sellableBatches()
                ->orderBy('created_at')
                ->orderBy('id'),
        ])
            ->where('created_by', auth()->id())
            ->where('is_active', true)
            ->where(function ($query) use ($search, $barcodeSearch) {
                $query->where('name', 'like', $search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', $search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%');

                if ($barcodeSearch !== '') {
                    $query->orWhereRaw("REPLACE(barcode, ' ', '') = ?", [$barcodeSearch])
                        ->orWhereRaw("REPLACE(barcode, ' ', '') like ?", [$barcodeSearch.'%'])
                        ->orWhereRaw("REPLACE(barcode, ' ', '') like ?", ['%'.$barcodeSearch.'%']);
                }
            })
            ->orderByRaw(
                "CASE
                    WHEN REPLACE(COALESCE(barcode, ''), ' ', '') = ? THEN 0
                    WHEN REPLACE(COALESCE(barcode, ''), ' ', '') LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    WHEN LOWER(name) LIKE ? OR REPLACE(COALESCE(barcode, ''), ' ', '') LIKE ? THEN 3
                    ELSE 4
                END",
                [
                    $barcodeSearch,
                    $barcodeSearch.'%',
                    mb_strtolower($search).'%',
                    '%'.mb_strtolower($search).'%',
                    '%'.$barcodeSearch.'%',
                ]
            )
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json([
            'products' => $products->map(fn (Product $product) => $this->productSearchPayload($product))->values(),
        ]);
    }

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

        $products = $query->latest()->paginate(15)->withQueryString();

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
            'barcode' => ['nullable', 'string', 'max:13', 'regex:/^\d+$/'],
            'category_id' => ['required', 'exists:categories,id'],
            'alert_quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'is_direct_restock_eligible' => ['boolean'],
        ], [
            'sku.unique' => 'Ce code SKU existe déjà.',
            'barcode.max' => 'Le code-barres ne doit pas depasser 13 chiffres.',
            'barcode.regex' => 'Le code-barres doit contenir uniquement des chiffres.',
            'selling_price.gte' => 'Le prix de vente doit être supérieur ou égal au prix d\'achat.',
        ]);

        // Vérifier que la catégorie est disponible pour le manager.
        if ($conflictingProduct = $this->barcodeConflict($validated['barcode'] ?? null)) {
            return back()
                ->withErrors(['barcode' => 'Code-barres deja existant chez vous.'])
                ->withInput()
                ->with('barcode_conflict_product_name', $conflictingProduct->name);
        }

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
            'is_direct_restock_eligible' => $validated['is_direct_restock_eligible'] ?? false,
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
            'barcode' => ['nullable', 'string', 'max:13', 'regex:/^\d+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'exists:categories,id'],
            'alert_quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'is_direct_restock_eligible' => ['boolean'],
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

        if ($conflictingProduct = $this->barcodeConflict($validated['barcode'] ?? null, $product)) {
            return back()
                ->withErrors(['barcode' => 'Code-barres deja existant chez vous.'])
                ->withInput()
                ->with('barcode_conflict_product_name', $conflictingProduct->name);
        }

        $oldValues = $product->only(['name', 'sku']);

        $product->update([
            'name' => $validated['name'],
            'barcode' => ($validated['barcode'] ?? null) ?: null,
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'alert_quantity' => $validated['alert_quantity'],
            'unit' => $validated['unit'],
            'is_active' => $validated['is_active'] ?? $product->is_active,
            'is_direct_restock_eligible' => $validated['is_direct_restock_eligible'] ?? false,
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

        $successMessage = 'Produit mis à jour avec succès.';

        if ($request->boolean('modal')) {
            return response()->view('manager.products._modal-success', [
                'message' => $successMessage,
                'redirectUrl' => route('manager.products.index'),
            ]);
        }

        return redirect()->route('manager.products.index')
            ->with('success', $successMessage);
    }

    /**
     * Supprimer definitivement un produit sans ventes liees.
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

        $product->forceDelete();

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

    public function exportExcel()
    {
        return Excel::download(
            new ProductsExport(auth()->id()),
            'produits_manager_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    public function exportCsv()
    {
        return Excel::download(
            new ProductsExport(auth()->id(), true),
            'produits_manager_' . now()->format('Y-m-d_H-i-s') . '.csv',
            ExcelFormat::CSV
        );
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'products_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ], [
            'products_file.required' => 'Choisissez un fichier CSV ou Excel.',
            'products_file.mimes' => 'Le fichier doit etre au format CSV ou Excel.',
        ]);

        $import = new ProductsImport(auth()->user());
        Excel::import($import, $validated['products_file']);

        $message = "{$import->createdCount()} produit(s) importe(s) avec succes.";
        if ($import->skippedRows()) {
            $message .= ' Lignes ignorees: ' . implode(' ', array_slice($import->skippedRows(), 0, 5));
        }

        return redirect()->route('manager.products.index')->with('success', $message);
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

    private function productSearchPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'category' => $product->category?->name,
            'quantity' => $product->quantity,
            'unit' => $product->unit,
            'alert_quantity' => $product->alert_quantity,
            'purchase_price' => (float) $product->purchase_price,
            'selling_price' => (float) $product->selling_price,
            'promotions' => $product->promotions->map(fn ($promotion) => [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'promotion_price' => (float) $promotion->promotion_price,
                'min_quantity' => $promotion->min_quantity,
                'status' => $promotion->status,
                'delete_url' => route('manager.promotions.destroy', $promotion),
            ])->values(),
            'batches' => $product->stockMovements->map(fn ($batch) => [
                'code' => $batch->batch_code ?? 'LOT-'.$batch->id,
                'remaining_quantity' => $batch->remaining_quantity,
                'selling_price' => (float) $batch->selling_price,
            ])->values(),
        ];
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

    private function barcodeConflict(?string $barcode, ?Product $currentProduct = null): ?Product
    {
        if (!$barcode) {
            return null;
        }

        $conflictingProduct = Product::withTrashed()
            ->where('barcode', $barcode)
            ->where('created_by', auth()->id())
            ->when($currentProduct, fn ($query) => $query->whereKeyNot($currentProduct->id))
            ->first();

        if ($conflictingProduct?->trashed() && $conflictingProduct->saleItems()->count() === 0) {
            $conflictingProduct->forceDelete();

            return null;
        }

        return $conflictingProduct;
    }

}
