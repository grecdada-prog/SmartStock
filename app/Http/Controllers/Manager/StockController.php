<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    /**
     * Vue d'ensemble du stock
     */
    public function index(Request $request)
    {
        $query = Product::where('created_by', auth()->id())
            ->with(['category'])
            ->withCount('stockMovements');

        // Filtres
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
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

        $products = $query->latest()->paginate(20);

        // Statistiques
        $stats = [
            'total_products' => Product::where('created_by', auth()->id())->count(),
            'low_stock_count' => Product::where('created_by', auth()->id())->lowStock()->count(),
            'out_of_stock_count' => Product::where('created_by', auth()->id())->where('quantity', '<=', 0)->count(),
            'total_value' => Product::where('created_by', auth()->id())->sum(DB::raw('quantity * purchase_price')),
        ];

        $categories = \App\Models\Category::where('created_by', auth()->id())
            ->active()
            ->orderBy('name')
            ->get();

        $restockProducts = Product::where('created_by', auth()->id())
            ->with('category')
            ->active()
            ->orderBy('name')
            ->get();

        return view('manager.stock.index', compact('products', 'stats', 'categories', 'restockProducts'));
    }

    /**
     * Produits en rupture de stock
     */
    public function lowStock()
    {
        $products = Product::where('created_by', auth()->id())
            ->with(['category'])
            ->lowStock()
            ->active()
            ->orderBy('quantity', 'asc')
            ->paginate(20);

        return view('manager.stock.low-stock', compact('products'));
    }

    /**
     * Afficher le formulaire de réapprovisionnement
     */
    public function showRestockForm()
    {
        $products = Product::where('created_by', auth()->id())
            ->with('category')
            ->active()
            ->orderBy('name')
            ->get();

        return view('manager.stock.restock', compact('products'));
    }

    /**
     * Réapprovisionner le stock
     */
    public function restock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0', 'gte:purchase_price'],
            'reference' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'selling_price.gte' => 'Le prix de vente doit etre superieur ou egal au prix d achat.',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Vérifier que le produit appartient au manager
        $this->authorize('update', $product);

        DB::transaction(function () use ($product, $validated) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $quantityBefore = $product->quantity;
            $quantityAfter = $quantityBefore + $validated['quantity'];

            // Créer le mouvement de stock
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'purchase_price' => $validated['purchase_price'],
                'selling_price' => $validated['selling_price'],
                'remaining_quantity' => $validated['quantity'],
                'reference' => $validated['reference'] ?? null,
                'reason' => $validated['reason'] ?? 'Réapprovisionnement',
                'user_id' => auth()->id(),
            ]);

            $movement->update([
                'batch_code' => 'LOT-' . str_pad((string) $movement->id, 6, '0', STR_PAD_LEFT),
            ]);

            // Mettre à jour la quantité du produit
            $product->update([
                'quantity' => $quantityAfter,
                'purchase_price' => $validated['purchase_price'],
                'selling_price' => $validated['selling_price'],
            ]);

            ActivityLog::log(
                'stock_restock',
                "Réapprovisionnement de {$product->name} : +{$validated['quantity']} {$product->unit}",
                'Product',
                $product->id
            );
        });

        return redirect()->route('manager.stock.index')
            ->with('success', "Stock réapprovisionné avec succès pour {$product->name}.");
    }

    /**
     * Ajuster le stock manuellement
     */
    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'new_quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Vérifier que le produit appartient au manager
        $this->authorize('update', $product);

        DB::transaction(function () use ($product, $validated) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $quantityBefore = $product->quantity;
            $quantityAfter = $validated['new_quantity'];
            $difference = $quantityAfter - $quantityBefore;

            // Créer le mouvement de stock
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => abs($difference),
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference' => null,
                'reason' => $validated['reason'],
                'user_id' => auth()->id(),
            ]);

            // Mettre à jour la quantité du produit
            $product->update([
                'quantity' => $quantityAfter,
            ]);

            ActivityLog::log(
                'stock_adjustment',
                "Ajustement de stock pour {$product->name} : {$quantityBefore} → {$quantityAfter} {$product->unit}",
                'Product',
                $product->id
            );
        });

        return back()->with('success', "Stock ajusté avec succès pour {$product->name}.");
    }

    /**
     * Historique des mouvements de stock
     */
    public function movements(Request $request)
    {
        $query = StockMovement::with(['product.category', 'user'])
            ->whereHas('product', function($q) {
                $q->where('created_by', auth()->id());
            });

        // Filtres
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->latest()->paginate(20);

        $products = Product::where('created_by', auth()->id())
            ->active()
            ->orderBy('name')
            ->get();

        return view('manager.stock.movements', compact('movements', 'products'));
    }

    /**
     * Retirer du stock (sortie manuelle)
     */
    public function remove(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Vérifier que le produit appartient au manager
        $this->authorize('update', $product);

        // Vérifier qu'il y a assez de stock
        DB::transaction(function () use ($product, $validated) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($product->quantity < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuffisant pour {$product->name}. Stock actuel : {$product->quantity} {$product->unit}",
                ]);
            }

            $quantityBefore = $product->quantity;
            $quantityAfter = $quantityBefore - $validated['quantity'];

            // Créer le mouvement de stock
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $validated['quantity'],
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference' => null,
                'reason' => $validated['reason'],
                'user_id' => auth()->id(),
            ]);

            // Mettre à jour la quantité du produit
            $product->update([
                'quantity' => $quantityAfter,
            ]);

            ActivityLog::log(
                'stock_removal',
                "Retrait de stock pour {$product->name} : -{$validated['quantity']} {$product->unit}",
                'Product',
                $product->id
            );
        });

        return back()->with('success', "Stock retiré avec succès pour {$product->name}.");
    }
}
