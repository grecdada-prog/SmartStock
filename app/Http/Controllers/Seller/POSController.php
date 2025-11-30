<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class POSController extends Controller
{
    /**
     * Afficher l'interface POS (Point de Vente)
     */
    public function index()
    {
        // Récupérer tous les produits actifs avec stock > 0
        $products = Product::where('is_active', true)
            ->where('quantity', '>', 0)
            ->with('category')
            ->orderBy('name')
            ->get();

        // Catégories pour le filtre
        $categories = Product::where('is_active', true)
            ->where('quantity', '>', 0)
            ->with('category')
            ->get()
            ->pluck('category')
            ->unique('id')
            ->values();

        // Statistiques du jour
        $todayStats = [
            'sales_count' => Sale::where('seller_id', auth()->id())
                ->whereDate('created_at', today())
                ->count(),
            'sales_total' => Sale::where('seller_id', auth()->id())
                ->whereDate('created_at', today())
                ->sum('total'),
            'items_sold' => SaleItem::whereHas('sale', function($query) {
                $query->where('seller_id', auth()->id())
                    ->whereDate('created_at', today());
            })->sum('quantity'),
        ];

        return view('seller.pos.index', compact('products', 'categories', 'todayStats'));
    }

    /**
     * Traiter une vente
     */
    public function processSale(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,card,mobile_money'],
            'amount_received' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Veuillez ajouter au moins un produit.',
            'items.min' => 'Veuillez ajouter au moins un produit.',
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
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    // Vérifier que le produit est actif
                    if (!$product->is_active) {
                        throw new \Exception("Le produit '{$product->name}' n'est plus disponible.");
                    }

                    // Vérifier le stock disponible
                    if ($product->quantity < $item['quantity']) {
                        throw new \Exception("Stock insuffisant pour '{$product->name}'. Disponible: {$product->quantity}");
                    }

                    $subtotal = $item['price'] * $item['quantity'];
                    $totalAmount += $subtotal;

                    $itemsData[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $subtotal,
                    ];
                }

                // 2. Créer la vente
                $sale = Sale::create([
                    'seller_id' => auth()->id(),
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'total' => $totalAmount,
                    'payment_method' => $validated['payment_method'],
                    'amount_received' => $validated['amount_received'] ?? $totalAmount,
                    'change_given' => max(0, ($validated['amount_received'] ?? $totalAmount) - $totalAmount),
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
                        'price' => $itemData['price'],
                        'subtotal' => $itemData['subtotal'],
                    ]);

                    // Déduire le stock
                    $quantityBefore = $itemData['product']->quantity;
                    $quantityAfter = $quantityBefore - $itemData['quantity'];

                    $itemData['product']->update([
                        'quantity' => $quantityAfter
                    ]);

                    // Enregistrer le mouvement de stock
                    StockMovement::create([
                        'product_id' => $itemData['product']->id,
                        'user_id' => auth()->id(),
                        'type' => 'sale',
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityAfter,
                        'quantity_moved' => $itemData['quantity'],
                        'unit_price' => $itemData['price'],
                        'total_value' => $itemData['subtotal'],
                        'reference' => "Vente #{$sale->invoice_number}",
                        'notes' => "Vente enregistrée via POS",
                    ]);
                }

                // 4. Logger l'activité
                ActivityLog::log(
                    'sale_created',
                    "Vente créée : #{$sale->invoice_number} - Total: " . number_format($totalAmount, 0, ',', ' ') . " FCFA",
                    'Sale',
                    $sale->id
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
                'message' => $e->getMessage(),
            ], 422);
        }
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
        if ($sale->seller_id !== auth()->id()) {
            abort(403, 'Vous n\'avez pas l\'autorisation de voir cette vente.');
        }

        $sale->load(['items.product', 'seller']);

        return view('seller.sales.show', compact('sale'));
    }

    /**
     * Imprimer le reçu d'une vente
     */
    public function printReceipt(Sale $sale)
    {
        // Vérifier que la vente appartient au vendeur
        if ($sale->seller_id !== auth()->id()) {
            abort(403, 'Vous n\'avez pas l\'autorisation d\'imprimer ce reçu.');
        }

        $sale->load(['items.product', 'seller']);

        return view('seller.pos.receipt', compact('sale'));
    }

    /**
     * Générer un numéro de facture unique
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');

        // Trouver le dernier numéro du jour
        $lastSale = Sale::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale && Str::startsWith($lastSale->invoice_number, $prefix . $date)) {
            // Extraire le numéro de séquence et incrémenter
            $lastNumber = (int) substr($lastSale->invoice_number, -4);
            $sequence = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // Premier numéro du jour
            $sequence = '0001';
        }

        return $prefix . $date . $sequence;
    }

    /**
     * Rechercher des produits (API pour l'interface POS)
     */
    public function searchProducts(Request $request)
    {
        $search = $request->get('q');

        $products = Product::where('is_active', true)
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
