<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\ActivityLog;
use App\Services\CashRegisterService;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    /**
     * Afficher l'interface POS (Point de Vente)
     */
    public function index()
    {
        $managerId = auth()->user()->created_by;
        // Récupérer tous les produits actifs avec stock > 0
        $products = $this->availableProductsForManager($managerId)->get();

        // Catégories pour le filtre
        $categories = Product::where('is_active', true)
            ->where('created_by', $managerId)
            ->where('quantity', '>', 0)
            ->with('category')
            ->get()
            ->pluck('category')
            ->unique('id')
            ->values();

        return view('seller.pos.index', compact('products', 'categories'));
    }

    public function products()
    {
        $managerId = auth()->user()->created_by;

        return response()->json([
            'success' => true,
            'products' => $this->availableProductsForManager($managerId)->get(),
        ]);
    }

    /**
     * Traiter une vente
     */
    public function processSale(Request $request)
    {
        if (app(CashRegisterService::class)->isClosedForSeller(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'La caisse est fermee. Ouvrez la caisse depuis le dashboard avant de reprendre les ventes.',
            ], 423);
        }

        // Validation
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,card,mobile_money'],
            'amount_received' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['required_if:payment_method,card,mobile_money', 'nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Veuillez ajouter au moins un produit.',
            'items.min' => 'Veuillez ajouter au moins un produit.',
            'amount_received.required_if' => 'Le montant recu est obligatoire pour un paiement en especes.',
            'customer_phone.required_if' => 'Le numero de telephone est obligatoire pour Orange Money et MTN Momo.',
            'payment_method.required' => 'Veuillez sélectionner une méthode de paiement.',
            'payment_method.in' => 'Méthode de paiement invalide.',
        ]);

        try {
            // Traiter la vente dans une transaction
            $sale = DB::transaction(function () use ($validated, $request) {
                $totalAmount = 0;
                $itemsData = [];

                // 1. Valider les stocks et calculer le total
                foreach ($validated['items'] as $item) {
                    $product = Product::where('created_by', auth()->user()->created_by)
                        ->lockForUpdate()
                        ->findOrFail($item['product_id']);

                    // Vérifier que le produit est actif
                    if (!$product->is_active) {
                        throw new \Exception("Le produit '{$product->name}' n'est plus disponible.");
                    }

                    // Vérifier le stock disponible
                    if ($product->quantity < $item['quantity']) {
                        throw new \Exception("Stock insuffisant pour '{$product->name}'. Disponible: {$product->quantity}");
                    }

                    $unitPrice = (float) $product->selling_price;
                    $subtotal = $unitPrice * $item['quantity'];
                    $totalAmount += $subtotal;

                    $itemsData[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ];
                }

                // 2. Créer la vente
                $amountReceived = $validated['amount_received'] ?? $totalAmount;

                if ($validated['payment_method'] === 'cash' && $amountReceived < $totalAmount) {
                    throw new \Exception('Le montant recu doit couvrir le total de la vente.');
                }

                $sale = Sale::create([
                    'seller_id' => auth()->id(),
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'subtotal' => $totalAmount,
                    'total' => $totalAmount,
                    'payment_method' => $validated['payment_method'],
                    'amount_received' => $amountReceived,
                    'change_given' => max(0, $amountReceived - $totalAmount),
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // 3. Créer les items de vente et déduire le stock
                foreach ($itemsData as $itemData) {
                    // Créer l'item de vente
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $itemData['product']->id,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['price'],
                        'subtotal' => $itemData['subtotal'],
                    ]);

                    // Déduire le stock
                    $quantityBefore = $itemData['product']->quantity;
                    $quantityAfter = $quantityBefore - $itemData['quantity'];

                    $itemData['product']->update([
                        'quantity' => $quantityAfter
                    ]);

                    $remainingToConsume = $itemData['quantity'];
                    $consumedBatches = [];

                    $batches = StockMovement::where('product_id', $itemData['product']->id)
                        ->where('type', 'in')
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remainingToConsume <= 0) {
                            break;
                        }

                        $taken = min($remainingToConsume, $batch->remaining_quantity);
                        $batch->update([
                            'remaining_quantity' => $batch->remaining_quantity - $taken,
                        ]);

                        $consumedBatches[] = ($batch->batch_code ?? 'LOT-' . $batch->id) . ':' . $taken;
                        $remainingToConsume -= $taken;
                    }

                    // Enregistrer le mouvement de stock
                    StockMovement::create([
                        'product_id' => $itemData['product']->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'quantity' => $itemData['quantity'],
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityAfter,
                        'selling_price' => $itemData['price'],
                        'reference' => "Vente #{$sale->invoice_number}",
                        'reason' => 'Vente enregistree via POS' . ($consumedBatches ? ' | Lots: ' . implode(', ', $consumedBatches) : ''),
                    ]);
                }

                // 4. Logger l'activité
                ActivityLog::log(
                    'sale_created',
                    "Vente créée : #{$sale->invoice_number} - Total: " . number_format($totalAmount, 0, ',', ' ') . " FCFA",
                    'Sale',
                    $sale->id,
                    [
                        'invoice_number' => $sale->invoice_number,
                        'seller_id' => auth()->id(),
                        'total' => $totalAmount,
                        'items_count' => count($itemsData),
                        'payment_method' => $validated['payment_method'],
                    ]
                );

                return $sale;
            });

            return response()->json([
                'success' => true,
                'message' => 'Vente enregistrée avec succès !',
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => $sale->total,
                'change' => $sale->change_given,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->friendlySaleError($e),
            ], 422);
        }
    }

    private function friendlySaleError(\Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Stock insuffisant')) {
            return $message;
        }

        if (str_contains($message, "n'est plus disponible")) {
            return $message;
        }

        if (str_contains($message, 'No query results')) {
            return 'Produit introuvable ou non autorise pour ce vendeur.';
        }

        if (str_contains($message, 'montant recu')) {
            return 'Le montant recu ne couvre pas le total de la vente.';
        }

        return 'La vente n\'a pas pu etre enregistree. Verifiez le panier et reessayez.';
    }

    private function availableProductsForManager(int $managerId)
    {
        return Product::where('is_active', true)
            ->where('created_by', $managerId)
            ->where('quantity', '>', 0)
            ->with('category')
            ->orderBy('name');
    }

    /**
     * Afficher l'historique des ventes du vendeur
     */
    public function salesHistory(Request $request)
    {
        $query = Sale::where('seller_id', auth()->id())
            ->with(['items.product']);

        // Filtres
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->search . '%');
            });
        }

        $sales = $query->latest()->paginate(20);

        // Statistiques
        $stats = [
            'total_sales' => Sale::where('seller_id', auth()->id())->count(),
            'total_revenue' => Sale::where('seller_id', auth()->id())->sum('total'),
            'today_sales' => Sale::where('seller_id', auth()->id())->whereDate('created_at', today())->count(),
            'today_revenue' => Sale::where('seller_id', auth()->id())->whereDate('created_at', today())->sum('total'),
            'this_month_sales' => Sale::where('seller_id', auth()->id())
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'this_month_revenue' => Sale::where('seller_id', auth()->id())
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total'),
        ];

        return view('seller.sales.history', compact('sales', 'stats'));
    }

    /**
     * Afficher les détails d'une vente
     */
    public function showSale(Sale $sale)
    {
        // Vérifier que la vente appartient au vendeur
        $this->authorize('view', $sale);

        $sale->load(['items.product', 'seller']);

        return view('seller.sales.show', compact('sale'));
    }

    /**
     * Imprimer le reçu d'une vente
     */
    public function printReceipt(Sale $sale)
    {
        // Vérifier que la vente appartient au vendeur
        $this->authorize('printReceipt', $sale);

        $sale->load(['items.product', 'seller']);

        return view('seller.pos.receipt', compact('sale'));
    }

    /**
     * Générer un numéro de facture unique
     */
    private function generateInvoiceNumber(): string
    {
        return Sale::generateInvoiceNumber();



    }

    /**
     * Rechercher des produits (API pour l'interface POS)
     */
    public function searchProducts(Request $request)
    {
        $search = $request->get('q');

        $products = Product::where('is_active', true)
            ->where('created_by', auth()->user()->created_by)
            ->where('quantity', '>', 0)
            ->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%');
            })
            ->with('category')
            ->limit(10)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->selling_price,
                    'quantity' => $product->quantity,
                    'category' => $product->category->name ?? 'N/A',
                ];
            });

        return response()->json($products);
    }
}
