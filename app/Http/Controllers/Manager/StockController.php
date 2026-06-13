<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductPromotion;
use App\Models\StockMovement;
use App\Models\ActivityLog;
use App\Models\User;
use App\Exports\RestocksExport;
use App\Services\StockRestockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;

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
            $search = $request->search;
            $barcodeSearch = preg_replace('/\D+/', '', $search);

            $query->where(function($q) use ($search, $barcodeSearch) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('barcode', 'like', '%' . $search . '%');

                if ($barcodeSearch !== '') {
                    $q->orWhereRaw("REPLACE(barcode, ' ', '') like ?", ['%' . $barcodeSearch . '%']);
                }
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

        $products = $query->latest()->paginate(15)->withQueryString();

        // Statistiques
        $stats = [
            'total_products' => Product::where('created_by', auth()->id())->count(),
            'low_stock_count' => Product::where('created_by', auth()->id())->lowStock()->count(),
            'out_of_stock_count' => Product::where('created_by', auth()->id())->where('quantity', '<=', 0)->count(),
            'total_value' => Product::where('created_by', auth()->id())->sum(DB::raw('quantity * purchase_price')),
        ];

        $superAdminIds = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->pluck('id');

        $categories = \App\Models\Category::where(function ($query) use ($superAdminIds) {
                $query->where('created_by', auth()->id())
                    ->orWhereIn('created_by', $superAdminIds);
            })
            ->active()
            ->orderBy('name')
            ->get();

        $restockProducts = Product::where('created_by', auth()->id())
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'quantity', 'unit']);

        return view('manager.stock.index', compact('products', 'stats', 'categories', 'restockProducts'));
    }

    /**
     * Produits en rupture de stock
     */
    public function lowStock()
    {
        $products = $this->lowStockExportQuery()->paginate(15)->withQueryString();
        $printProducts = $this->lowStockExportQuery()->get();

        return view('manager.stock.low-stock', compact('products', 'printProducts'));
    }

    public function exportLowStockPdf()
    {
        $products = $this->lowStockExportQuery()->get();

        ActivityLog::log(
            'export_low_stock_pdf',
            'Export PDF des produits en stock faible',
            'Product',
            null
        );

        $pdf = Pdf::loadView('manager.stock.low-stock-pdf', [
            'products' => $products,
            'manager' => auth()->user(),
        ]);

        return $pdf->download('stock_faible_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    public function expiryAlerts(Request $request)
    {
        $days = 10;
        $status = $request->input('status', 'all');

        $query = $this->expiryAlertsQuery($days);

        if ($status === 'expired') {
            $query->whereDate('expiration_date', '<', today());
        } elseif ($status === 'soon') {
            $query->whereDate('expiration_date', '>=', today())
                ->whereDate('expiration_date', '<=', today()->addDays($days));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $barcodeSearch = preg_replace('/\D+/', '', $search);

            $query->where(function ($movementQuery) use ($search, $barcodeSearch) {
                $movementQuery
                    ->where('batch_code', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('product', function ($productQuery) use ($search, $barcodeSearch) {
                        $productQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('barcode', 'like', '%'.$search.'%');

                        if ($barcodeSearch !== '') {
                            $productQuery->orWhereRaw("REPLACE(barcode, ' ', '') like ?", ['%'.$barcodeSearch.'%']);
                        }
                    });
            });
        }

        $movements = $query
            ->orderBy('expiration_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $baseStatsQuery = $this->expiryAlertsQuery($days);

        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'expired' => (clone $baseStatsQuery)->whereDate('expiration_date', '<', today())->count(),
            'soon' => (clone $baseStatsQuery)
                ->whereDate('expiration_date', '>=', today())
                ->whereDate('expiration_date', '<=', today()->addDays($days))
                ->count(),
            'remaining_quantity' => (clone $baseStatsQuery)->sum('remaining_quantity'),
            'days' => $days,
        ];

        return view('manager.stock.expiry-alerts', compact('movements', 'stats', 'status'));
    }

    private function lowStockExportQuery()
    {
        return Product::where('created_by', auth()->id())
            ->with(['category', 'latestPurchaseMovement'])
            ->lowStock()
            ->active()
            ->orderByRaw('CASE WHEN quantity > 0 AND quantity <= alert_quantity THEN 0 ELSE 1 END')
            ->orderBy('quantity', 'asc')
            ->orderBy('name');
    }

    private function expiryAlertsQuery(int $days)
    {
        return StockMovement::with(['product.category', 'user'])
            ->whereIn('type', [StockMovement::TYPE_IN, StockMovement::TYPE_CORRECTION_CANCELLATION])
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', today()->addDays($days))
            ->whereHas('product', fn ($query) => $query
                ->where('created_by', auth()->id())
                ->where('is_active', true));
    }

    /**
     * Afficher le formulaire de réapprovisionnement
     */
    public function showRestockForm(Request $request)
    {
        $prefillProduct = null;
        $aiRecommendedQuantity = null;

        if ($request->filled('product_id')) {
            $product = Product::with([
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
                ->find($request->integer('product_id'));

            if ($product) {
                $prefillProduct = $this->productSearchPayload($product);
                $aiRecommendedQuantity = max(1, (int) $request->query('quantity', 1));
            }
        }

        return view('manager.stock.restock', compact('prefillProduct', 'aiRecommendedQuantity'));
    }

    /**
     * Réapprovisionner le stock
     */
    public function restock(Request $request, StockRestockService $restocks)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0', 'gte:purchase_price'],
            'non_perishable' => ['nullable', 'boolean'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:today', 'required_unless:non_perishable,1'],
            'promotion_prices' => ['nullable', 'array'],
            'promotion_prices.*' => ['nullable', 'numeric', 'min:1'],
        ], [
            'selling_price.gte' => 'Le prix de vente doit etre superieur ou egal au prix d achat.',
            'expiration_date.required_unless' => 'La date de peremption est obligatoire sauf si le produit est non perissable.',
            'expiration_date.after_or_equal' => 'La date de peremption doit etre aujourd hui ou une date future.',
            'promotion_prices.*.min' => 'Le montant promotion doit etre superieur a 0.',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Vérifier que le produit appartient au manager
        $this->authorize('update', $product);

        $restocks->restock($product, auth()->user(), $validated);

        return redirect()->route('manager.stock.index')
            ->with('success', "Stock reapprovisionne avec succes pour {$product->name} !");
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

        $movements = $query->latest()->paginate(15)->withQueryString();
        $selectedProduct = null;

        if ($request->filled('product_id')) {
            $selectedProduct = Product::where('created_by', auth()->id())->find($request->product_id);
        }

        $products = Product::where('created_by', auth()->id())
            ->active()
            ->orderBy('name')
            ->get();

        return view('manager.stock.movements', compact('movements', 'products', 'selectedProduct'));
    }

    /**
     * Historique cible d'un produit.
     */
    public function productMovements(Product $product)
    {
        $this->authorize('view', $product);

        return redirect()->route('manager.stock.movements', ['product_id' => $product->id]);
    }

    /**
     * Historique dedie aux approvisionnements.
     */
    public function restocks(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [
            'date_to.after_or_equal' => 'La date de fin doit etre egale ou posterieure a la date de debut.',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        $restocks = $this->restocksQuery($dateFrom, $dateTo)
            ->latest()
            ->when(! $dateFrom && ! $dateTo, fn ($q) => $q->limit(30))
            ->get();
        $totalValue = (float) $restocks->sum(fn (StockMovement $movement) => $this->restockValue($movement));

        return view('manager.stock.restocks', compact('restocks', 'dateFrom', 'dateTo', 'totalValue'));
    }

    public function exportRestocksPdf(Request $request)
    {
        [$dateFrom, $dateTo] = $this->validatedRestockExportDates($request);
        $restocks = $this->restocksQuery($dateFrom, $dateTo)->latest()->get();

        ActivityLog::log(
            'export_restocks_pdf',
            'Export PDF des approvisionnements',
            'StockMovement',
            null,
            ['date_from' => $dateFrom, 'date_to' => $dateTo]
        );

        $pdf = Pdf::loadView('manager.stock.restocks-pdf', [
            'restocks' => $restocks,
            'manager' => auth()->user(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('approvisionnements_' . $dateFrom . '_' . ($dateTo ?: $dateFrom) . '.pdf');
    }

    public function exportRestocksExcel(Request $request)
    {
        [$dateFrom, $dateTo] = $this->validatedRestockExportDates($request);

        ActivityLog::log(
            'export_restocks_excel',
            'Export Excel des approvisionnements',
            'StockMovement',
            null,
            ['date_from' => $dateFrom, 'date_to' => $dateTo]
        );

        return Excel::download(
            new RestocksExport(auth()->id(), $dateFrom, $dateTo),
            'approvisionnements_' . $dateFrom . '_' . ($dateTo ?: $dateFrom) . '.xlsx',
            ExcelFormat::XLSX
        );
    }

    private function restocksQuery(?string $dateFrom = null, ?string $dateTo = null)
    {
        return StockMovement::with(['product.category', 'user'])
            ->where('type', 'in')
            ->whereHas('product', function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo ?: $dateFrom, fn ($query, $endDate) => $query->whereDate('created_at', '<=', $endDate));
    }

    private function restockValue(StockMovement $movement): float
    {
        return (float) $movement->quantity * (float) ($movement->purchase_price ?? 0);
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
            ])->values(),
            'batches' => $product->stockMovements->map(fn ($batch) => [
                'code' => $batch->batch_code ?? 'LOT-'.$batch->id,
                'remaining_quantity' => $batch->remaining_quantity,
                'selling_price' => (float) $batch->selling_price,
            ])->values(),
        ];
    }

    private function validatedRestockExportDates(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [
            'date_from.required' => 'Choisissez la date de debut.',
            'date_to.after_or_equal' => 'La date de fin doit etre egale ou posterieure a la date de debut.',
        ]);

        return [$validated['date_from'], $validated['date_to'] ?? null];
    }

    /**
     * Detail d'un approvisionnement.
     */
    public function showRestock(StockMovement $movement)
    {
        abort_unless($movement->type === 'in', 404);

        $movement->load(['product.category', 'user']);
        abort_if($movement->product === null, 404);

        $this->authorize('view', $movement->product);

        return view('manager.stock.restock-show', compact('movement'));
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

