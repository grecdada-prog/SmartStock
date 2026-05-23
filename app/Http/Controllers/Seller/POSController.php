<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        if (! app(CashRegisterService::class)->isOpenForSeller(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'La caisse n\'est pas ouverte. Ouvrez la caisse depuis le dashboard avant de commencer les ventes.',
            ], 423);
        }
        $this->normalizeContactInputs($request, ['customer_phone'], []);

        // Validation
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,card,mobile_money'],
            'amount_received' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['required_if:payment_method,card,mobile_money', ...$this->phoneRules()],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Veuillez ajouter au moins un produit.',
            'items.min' => 'Veuillez ajouter au moins un produit.',
            'amount_received.required_if' => 'Le montant recu est obligatoire pour un paiement en especes.',
            'customer_phone.required_if' => 'Le numero de telephone est obligatoire pour Orange Money et MTN Momo.',
            'customer_phone.regex' => 'Le numero de telephone doit contenir 9 a 15 chiffres.',
            'payment_method.required' => 'Veuillez sélectionner une méthode de paiement.',
            'payment_method.in' => 'Méthode de paiement invalide.',
        ]);

        if ($validated['payment_method'] !== 'cash' && ! $this->operatorForCameroonPhone($validated['customer_phone'] ?? null)) {
            throw ValidationException::withMessages([
                'customer_phone' => 'Numero non reconnu pour Orange Money ou MTN Momo Cameroun.',
            ]);
        }

        $validated['payment_method'] = $this->normalizePaymentMethod($validated['payment_method'], $validated['customer_phone'] ?? null);

        try {
            // Traiter la vente dans une transaction
            $sale = DB::transaction(function () use ($validated) {
                $totalAmount = 0;
                $itemsData = [];
                $requestedItems = collect($validated['items'])
                    ->groupBy('product_id')
                    ->map(fn ($items, $productId) => [
                        'product_id' => (int) $productId,
                        'quantity' => (int) $items->sum('quantity'),
                    ])
                    ->values();

                // 1. Valider les stocks et calculer le total
                foreach ($requestedItems as $item) {
                    $product = Product::where('created_by', auth()->user()->created_by)
                        ->lockForUpdate()
                        ->findOrFail($item['product_id']);

                    // Vérifier que le produit est actif
                    if (! $product->is_active) {
                        throw new \Exception("Le produit '{$product->name}' n'est plus disponible.");
                    }

                    // Vérifier le stock disponible
                    if ($product->quantity < $item['quantity']) {
                        throw new \Exception("Stock insuffisant pour '{$product->name}'. Disponible: {$product->quantity}");
                    }

                    $allocations = $this->fifoAllocationsForSale($product, $item['quantity']);
                    $subtotal = collect($allocations)->sum('subtotal');
                    $totalAmount += $subtotal;

                    $itemsData[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'allocations' => $allocations,
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
                    // Déduire le stock
                    $quantityBefore = $itemData['product']->quantity;
                    $quantityAfter = $quantityBefore - $itemData['quantity'];

                    $itemData['product']->update([
                        'quantity' => $quantityAfter,
                    ]);

                    $runningQuantityBefore = $quantityBefore;

                    foreach ($itemData['allocations'] as $allocation) {
                        $lineQuantityAfter = $runningQuantityBefore - $allocation['quantity'];

                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $itemData['product']->id,
                            'quantity' => $allocation['quantity'],
                            'unit_price' => $allocation['selling_price'],
                            'subtotal' => $allocation['subtotal'],
                        ]);

                        if ($allocation['batch']) {
                            $allocation['batch']->update([
                                'remaining_quantity' => $allocation['batch']->remaining_quantity - $allocation['quantity'],
                            ]);
                        }

                        // Enregistrer un mouvement distinct par lot garde la tracabilite exacte du prix FIFO.
                        StockMovement::create([
                            'product_id' => $itemData['product']->id,
                            'user_id' => auth()->id(),
                            'type' => 'out',
                            'quantity' => $allocation['quantity'],
                            'quantity_before' => $runningQuantityBefore,
                            'quantity_after' => $lineQuantityAfter,
                            'purchase_price' => $allocation['purchase_price'],
                            'selling_price' => $allocation['selling_price'],
                            'reference' => "Vente #{$sale->invoice_number}",
                            'reason' => 'Vente enregistree via POS | Lots: '.$allocation['batch_code'].':'.$allocation['quantity'],
                        ]);

                        $runningQuantityBefore = $lineQuantityAfter;
                    }
                }

                // 4. Logger l'activité
                ActivityLog::log(
                    'sale_created',
                    "Vente créée : #{$sale->invoice_number} - Total: ".number_format($totalAmount, 0, ',', ' ').' FCFA',
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

    private function normalizePaymentMethod(string $paymentMethod, ?string $customerPhone = null): string
    {
        if ($paymentMethod !== 'mobile_money') {
            return $paymentMethod;
        }

        return $this->operatorForCameroonPhone($customerPhone) === 'orange'
            ? 'card'
            : 'mobile_money';
    }

    private function operatorForCameroonPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '237') && strlen($digits) > 9) {
            $digits = substr($digits, 3);
        }

        if (strlen($digits) !== 9) {
            return null;
        }

        $prefix = (int) substr($digits, 0, 3);

        if (($prefix >= 650 && $prefix <= 654) || ($prefix >= 670 && $prefix <= 679) || ($prefix >= 680 && $prefix <= 683)) {
            return 'mtn';
        }

        if (($prefix >= 655 && $prefix <= 659) || ($prefix >= 685 && $prefix <= 689) || ($prefix >= 690 && $prefix <= 699)) {
            return 'orange';
        }

        return null;
    }

    private function availableProductsForManager(int $managerId)
    {
        $fifoSellingPrice = StockMovement::select('selling_price')
            ->whereColumn('product_id', 'products.id')
            ->where('type', 'in')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(1);

        return Product::where('is_active', true)
            ->where('created_by', $managerId)
            ->where('quantity', '>', 0)
            ->select('products.*')
            ->selectSub($fifoSellingPrice, 'fifo_selling_price')
            ->with([
                'category',
                'stockMovements' => fn ($query) => $query
                    ->where('type', 'in')
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->orderBy('name');
    }

    private function fifoAllocationsForSale(Product $product, int $quantity): array
    {
        $remainingToConsume = $quantity;
        $allocations = [];

        $batches = StockMovement::where('product_id', $product->id)
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
            $sellingPrice = (float) ($batch->selling_price ?? $product->selling_price);
            $purchasePrice = (float) ($batch->purchase_price ?? $product->purchase_price);

            $allocations[] = [
                'batch' => $batch,
                'batch_code' => $batch->batch_code ?? 'LOT-'.$batch->id,
                'quantity' => $taken,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'subtotal' => $taken * $sellingPrice,
            ];

            $remainingToConsume -= $taken;
        }

        if ($remainingToConsume > 0) {
            $sellingPrice = (float) $product->selling_price;
            $purchasePrice = (float) $product->purchase_price;

            $allocations[] = [
                'batch' => null,
                'batch_code' => 'Stock initial',
                'quantity' => $remainingToConsume,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'subtotal' => $remainingToConsume * $sellingPrice,
            ];
        }

        return $allocations;
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
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%'.$request->search.'%')
                    ->orWhere('customer_name', 'like', '%'.$request->search.'%')
                    ->orWhere('customer_phone', 'like', '%'.$request->search.'%');
            });
        }

        $sales = $query->latest()->get();

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
}

