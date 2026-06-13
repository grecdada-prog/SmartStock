<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductPromotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\AvlyTextSmsService;
use App\Services\CashRegisterService;
use App\Services\EnergyTokenRecentSaleService;
use App\Services\SocadelTokenService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class POSController extends Controller
{
    private const SMS_FEE = 25.0;

    /**
     * Afficher l'interface POS (Point de Vente)
     */
    public function index()
    {
        $managerId = auth()->user()->created_by;
        // Récupérer tous les produits actifs avec stock > 0
        $products = $this->availableProductsForManager($managerId)->get();

        return view('seller.pos.index', compact('products'));
    }

    public function products()
    {
        $managerId = auth()->user()->created_by;

        return response()->json([
            'success' => true,
            'products' => $this->availableProductsForManager($managerId)->get(),
        ]);
    }

    public function token()
    {
        return view('seller.tokens.create', [
            'addresses' => $this->tokenMeterAddresses(),
            'rooms' => $this->tokenRooms(),
            'ratePerKwh' => 120,
        ]);
    }

    public function sellToken(Request $request)
    {
        if (! app(CashRegisterService::class)->isOpenForSeller(auth()->user())) {
            return back()
                ->withErrors(['cash_register' => 'La caisse n\'est pas ouverte. Ouvrez la caisse depuis le dashboard avant de vendre un token.'])
                ->withInput();
        }

        $request->merge([
            'room_number' => Str::upper(preg_replace('/\s+/', '', (string) $request->input('room_number'))),
        ]);
        $this->normalizeContactInputs($request, ['customer_phone', 'sms_phone'], []);

        $validated = $request->validate([
            'meter_address' => ['required', 'string', 'in:'.implode(',', $this->tokenMeterAddresses())],
            'room_number' => ['required', 'string', 'in:'.implode(',', $this->tokenRooms())],
            'amount' => ['required', 'numeric', 'min:240', $this->tokenAmountMultipleRule()],
            'payment_method' => ['required', 'in:cash,mobile_money'],
            'customer_phone' => ['required_if:payment_method,mobile_money', ...$this->phoneRules()],
            'send_sms' => ['nullable', 'boolean'],
            'sms_phone' => ['required_if:send_sms,1', ...$this->phoneRules()],
            'confirmed_recent_token' => ['nullable', 'boolean'],
        ], [
            'meter_address.required' => 'Choisissez l\'adresse du compteur.',
            'meter_address.in' => 'Adresse compteur invalide.',
            'room_number.required' => 'Saisissez le numéro du bien.',
            'room_number.in' => 'Numéro du bien invalide pour ce compteur.',
            'amount.required' => 'Saisissez le montant a vendre.',
            'amount.min' => 'Le montant minimum est 240 FCFA.',
            'payment_method.required' => 'Choisissez le mode de paiement.',
            'payment_method.in' => 'Mode de paiement invalide.',
            'customer_phone.required_if' => 'Le numero de telephone est obligatoire pour un paiement mobile.',
            'customer_phone.regex' => 'Le numero de telephone doit contenir 9 a 15 chiffres.',
            'sms_phone.required_if' => 'Le numero SMS est obligatoire pour recevoir le token par SMS.',
            'sms_phone.regex' => 'Le numero SMS doit contenir 9 a 15 chiffres.',
        ]);
        $validated['send_sms'] = $request->boolean('send_sms');
        $validated['sms_phone'] = $validated['send_sms'] ? ($validated['sms_phone'] ?? null) : null;
        $validated['confirmed_recent_token'] = $request->boolean('confirmed_recent_token');

        if (! $validated['confirmed_recent_token']) {
            $recentSale = app(EnergyTokenRecentSaleService::class)->find($validated['meter_address'], $validated['room_number']);

            if ($recentSale) {
                return back()
                    ->withErrors([
                        'token_recent' => app(EnergyTokenRecentSaleService::class)
                            ->messageFor($recentSale, $validated['meter_address'], $validated['room_number']),
                    ])
                    ->withInput();
            }
        }

        // if ($validated['payment_method'] !== 'cash' && ! $this->operatorForCameroonPhone($validated['customer_phone'] ?? null)) {
        //     throw ValidationException::withMessages([
        //         'customer_phone' => 'Numero non reconnu pour Orange Money ou MTN Momo Cameroun.',
        //     ]);
        // }

        // if ($validated['payment_method'] !== 'cash') {
        //     return back()
        //         ->withErrors(['payment_method' => 'Le paiement mobile doit etre confirme par Monetbil avant enregistrement du token.'])
        //         ->withInput();
        // }

        $validated['payment_method'] = $this->normalizePaymentMethod($validated['payment_method'], $validated['customer_phone'] ?? null);
        $amount = (float) $validated['amount'];
        $smsFee = $validated['send_sms'] ? self::SMS_FEE : 0.0;
        $operatorFee = $this->operatorFeeForPayment($validated['payment_method'], $amount, 0.03);
        $totalToPay = $amount + $operatorFee + $smsFee;
        $kwh = round($amount / 120, 2);
        $socadelTokenService = app(SocadelTokenService::class);
        $tokenTransactionReference = $socadelTokenService->generateReference();

        try {
            $apiResponse = $socadelTokenService->sell($validated['meter_address'], $validated['room_number'], $amount, $tokenTransactionReference);
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['token_api' => $e->getMessage()])
                ->withInput();
        }

        $sale = DB::transaction(function () use ($validated, $amount, $operatorFee, $smsFee, $totalToPay, $kwh, $apiResponse, $tokenTransactionReference) {
            $smsNote = $validated['send_sms'] ? ' | SMS: '.$validated['sms_phone'].' | Frais SMS: '.number_format($smsFee, 0, ',', ' ').' FCFA' : '';

            $sale = Sale::create([
                'seller_id' => auth()->id(),
                'invoice_number' => $this->generateInvoiceNumber(),
                'subtotal' => $amount,
                'total' => $totalToPay,
                'payment_method' => $validated['payment_method'],
                'amount_received' => $totalToPay,
                'change_given' => 0,
                'customer_name' => $validated['room_number'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'notes' => "Token Energie | Adresse: {$validated['meter_address']} | Numéro du bien: {$validated['room_number']} | Token: {$apiResponse['token']} | Frais operateur: ".number_format($operatorFee, 0, ',', ' ').' FCFA'.$smsNote,
            ]);

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => null,
                'service_name' => 'Token Energie',
                'service_payload' => [
                    'meter_address' => $validated['meter_address'],
                    'room_number' => $validated['room_number'],
                    'kwh' => $kwh,
                    'rate_per_kwh' => 120,
                    'token_amount' => $amount,
                    'token_transaction' => $tokenTransactionReference,
                    'send_sms' => $validated['send_sms'],
                    'sms_phone' => $validated['sms_phone'],
                    'sms_fee' => $smsFee,
                    'sms_status' => $validated['send_sms'] ? 'pending' : null,
                    'operator_fee' => $operatorFee,
                    'payment_method' => $validated['payment_method'],
                    'total_to_pay' => $totalToPay,
                    'token' => $apiResponse['token'],
                    'api_status' => $apiResponse['status'],
                    'api_provider' => $apiResponse['provider'] ?? null,
                    'api_response' => $apiResponse['response_payload'] ?? null,
                ],
                'quantity' => 1,
                'unit_price' => $amount,
                'subtotal' => $amount,
            ]);

            ActivityLog::log(
                'energy_token_sold',
                "Token Energie vendu : {$validated['room_number']} - ".number_format($amount, 0, ',', ' ').' FCFA',
                'Sale',
                $sale->id,
                [
                    'invoice_number' => $sale->invoice_number,
                    'seller_id' => auth()->id(),
                    'meter_address' => $validated['meter_address'],
                    'room_number' => $validated['room_number'],
                    'kwh' => $kwh,
                    'token' => $apiResponse['token'],
                    'token_transaction' => $tokenTransactionReference,
                    'subtotal' => $amount,
                    'operator_fee' => $operatorFee,
                    'send_sms' => $validated['send_sms'],
                    'sms_phone' => $validated['sms_phone'],
                    'sms_fee' => $smsFee,
                    'total' => $totalToPay,
                    'payment_method' => $validated['payment_method'],
                ]
            );

            return $sale;
        });

        app(AvlyTextSmsService::class)->sendEnergyTokenSms($sale->load('items'));

        return redirect()
            ->route('seller.tokens.create')
            ->with('token_sale', [
                'invoice_number' => $sale->invoice_number,
                'address' => $validated['meter_address'],
                'room' => $validated['room_number'],
                'amount' => $amount,
                'operator_fee' => $operatorFee,
                'sms_fee' => $smsFee,
                'total' => $totalToPay,
                'kwh' => $kwh,
                'token' => $apiResponse['token'],
                'send_sms' => $validated['send_sms'],
                'sms_phone' => $validated['sms_phone'],
                'payment_method' => $sale->payment_method_label,
                'receipt_url' => route('seller.pos.receipt', $sale),
            ]);
    }

    /**
     * Vérifie si un token a déjà été vendu pour cette adresse + numéro du bien dans les 48 dernières heures.
     * Appelé en AJAX depuis le formulaire avant soumission — protège contre les double-ventes.
     */
    public function checkRecentTokenSale(Request $request): JsonResponse
    {
        $request->validate([
            'meter_address' => ['required', 'string'],
            'room_number'   => ['required', 'string'],
        ]);

        $recentItem = app(EnergyTokenRecentSaleService::class)
            ->find($request->meter_address, $request->room_number);

        if (! $recentItem) {
            return response()->json(['recent' => false]);
        }

        return response()->json([
            'recent'  => true,
            'message' => app(EnergyTokenRecentSaleService::class)
                ->messageFor($recentItem, $request->meter_address, $request->room_number),
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
            'items.*.apply_promotion' => ['nullable', 'boolean'],
            'items.*.promotion_id' => ['nullable', 'exists:product_promotions,id'],
            'payment_method' => ['required', 'in:cash,card,mobile_money'],
            'amount_received' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['required_if:payment_method,card,mobile_money', ...$this->phoneRules()],
            'notes' => ['nullable', 'string', 'max:500'],
            'client_sale_token' => ['nullable', 'string', 'max:100'],
        ], [
            'items.required' => 'Veuillez ajouter au moins un produit.',
            'items.min' => 'Veuillez ajouter au moins un produit.',
            'amount_received.required_if' => 'Le montant recu est obligatoire pour un paiement en especes.',
            'customer_phone.required_if' => 'Le numero de telephone est obligatoire pour Orange Money et MTN Momo.',
            'customer_phone.regex' => 'Le numero de telephone doit contenir 9 a 15 chiffres.',
            'payment_method.required' => 'Veuillez sélectionner une méthode de paiement.',
            'payment_method.in' => 'Méthode de paiement invalide.',
        ]);

        // if ($validated['payment_method'] !== 'cash' && ! $this->operatorForCameroonPhone($validated['customer_phone'] ?? null)) {
        //     throw ValidationException::withMessages([
        //         'customer_phone' => 'Numero non reconnu pour Orange Money ou MTN Momo Cameroun.',
        //     ]);
        // }

        // if ($validated['payment_method'] !== 'cash') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Le paiement mobile doit etre confirme par Monetbil avant enregistrement de la vente.',
        //         'requires_monetbil' => true,
        //     ], 409);
        // }

        $validated['payment_method'] = $this->normalizePaymentMethod($validated['payment_method'], $validated['customer_phone'] ?? null);
        $clientSaleToken = $validated['client_sale_token'] ?? null;

        if ($clientSaleToken) {
            $existingSale = $this->saleForClientToken($clientSaleToken);

            if ($existingSale) {
                return $this->saleSuccessResponse($existingSale, 'Vente deja enregistree.');
            }
        }

        try {
            // Traiter la vente dans une transaction
            $sale = DB::transaction(function () use ($validated, $clientSaleToken) {
                $totalAmount = 0;
                $itemsData = [];
                $requestedItems = collect($validated['items'])
                    ->groupBy('product_id')
                    ->map(function ($items, $productId) {
                        $requestedPromotion = $items->firstWhere('apply_promotion', true);

                        return [
                            'product_id' => (int) $productId,
                            'quantity' => (int) $items->sum('quantity'),
                            'apply_promotion' => (bool) $items->contains(fn ($item) => (bool) ($item['apply_promotion'] ?? false)),
                            'promotion_id' => $requestedPromotion['promotion_id'] ?? null,
                        ];
                    })
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

                    $promotion = $this->eligiblePromotionForSale($product, $item);
                    $allocations = $this->fifoAllocationsForSale($product, $item['quantity'], $promotion);
                    $subtotal = collect($allocations)->sum('subtotal');
                    $totalAmount += $subtotal;

                    $itemsData[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'promotion' => $promotion,
                        'allocations' => $allocations,
                        'subtotal' => $subtotal,
                    ];
                }

                // 2. Créer la vente
                $operatorFee = $this->operatorFeeForPayment($validated['payment_method'], $totalAmount);
                $totalToPay = $totalAmount + $operatorFee;
                $amountReceived = $validated['payment_method'] === 'cash'
                    ? ($validated['amount_received'] ?? $totalAmount)
                    : $totalToPay;

                if ($validated['payment_method'] === 'cash' && $amountReceived < $totalAmount) {
                    throw new \Exception('Le montant recu doit couvrir le total de la vente.');
                }

                $sale = Sale::create([
                    'seller_id' => auth()->id(),
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'client_sale_token' => $clientSaleToken,
                    'subtotal' => $totalAmount,
                    'total' => $totalToPay,
                    'payment_method' => $validated['payment_method'],
                    'amount_received' => $amountReceived,
                    'change_given' => max(0, $amountReceived - $totalToPay),
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
                            'promotion_id' => $allocation['promotion_id'],
                            'promotion_snapshot' => $allocation['promotion_snapshot'],
                            'quantity' => $allocation['quantity'],
                            'unit_price' => $allocation['selling_price'],
                            'original_unit_price' => $allocation['original_selling_price'],
                            'subtotal' => $allocation['subtotal'],
                            'discount_amount' => $allocation['discount_amount'],
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
                            'reason' => 'Vente enregistree via POS | Lots: '.$allocation['batch_code'].':'.$allocation['quantity'].$allocation['promotion_reason'],
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
                        'subtotal' => $totalAmount,
                        'operator_fee' => $operatorFee,
                        'total' => $totalToPay,
                        'items_count' => count($itemsData),
                        'payment_method' => $validated['payment_method'],
                    ]
                );

                return $sale;
            });

            return $this->saleSuccessResponse($sale, 'Vente enregistree avec succes !');

        } catch (QueryException $e) {
            if ($clientSaleToken && $this->isDuplicateClientTokenError($e)) {
                $existingSale = $this->saleForClientToken($clientSaleToken);

                if ($existingSale) {
                    return $this->saleSuccessResponse($existingSale, 'Vente deja enregistree.');
                }
            }

            return response()->json([
                'success' => false,
                'message' => $this->friendlySaleError($e),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $this->friendlySaleError($e),
            ], 422);
        }
    }

    private function saleForClientToken(string $clientSaleToken): ?Sale
    {
        return Sale::where('seller_id', auth()->id())
            ->where('client_sale_token', $clientSaleToken)
            ->first();
    }

    private function isDuplicateClientTokenError(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'client_sale_token')
            || str_contains($message, 'sales_client_sale_token_unique')
            || (str_contains($message, 'UNIQUE') && str_contains($message, 'sales'));
    }

    private function saleSuccessResponse(Sale $sale, string $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'total' => $sale->total,
            'change' => $sale->change_given,
        ]);
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

        if (str_contains($message, 'Promotion')) {
            return $message;
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

    private function operatorFeeForPayment(string $paymentMethod, float $amount, float $feeRate = 0.02): float
    {
        if ($paymentMethod === 'cash') {
            return 0.0;
        }

        return (float) round($amount * $feeRate);
    }

    private function tokenAmountMultipleRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (abs(fmod((float) $value, 120.0)) > 0.00001) {
                $fail('montant invalide. Veuillez saisir un multiple de 120.');
            }
        };
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

    private function tokenMeterAddresses(): array
    {
        return ['SMARTCITY 1', 'NKOZOA'];
    }

    private function tokenRooms(): array
    {
        $rooms = [];

        foreach (['E' => 10, 'D' => 13, 'C' => 13, 'B' => 13, 'A' => 13] as $prefix => $max) {
            for ($number = 1; $number <= $max; $number++) {
                $rooms[] = $prefix.$number;
            }
        }

        return $rooms;
    }

    private function availableProductsForManager(int $managerId)
    {
        $fifoSellingPrice = StockMovement::select('selling_price')
            ->whereColumn('product_id', 'products.id')
            ->sellableBatches()
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
                'activePromotion',
                'activePromotions',
                'stockMovements' => fn ($query) => $query
                    ->sellableBatches()
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->orderBy('name');
    }

    private function eligiblePromotionForSale(Product $product, array $item): ?ProductPromotion
    {
        if (! ($item['apply_promotion'] ?? false)) {
            return null;
        }

        $promotionId = $item['promotion_id'] ?? null;

        $promotion = ProductPromotion::where('manager_id', auth()->user()->created_by)
            ->where('product_id', $product->id)
            ->where('status', ProductPromotion::STATUS_ACTIVE)
            ->when($promotionId, fn ($query) => $query->where('id', $promotionId))
            ->first();

        if (! $promotion) {
            throw new \Exception("Promotion invalide pour '{$product->name}'.");
        }

        if ($item['quantity'] < $promotion->min_quantity) {
            throw new \Exception("Promotion non applicable pour '{$product->name}'. Quantite minimum: {$promotion->min_quantity}");
        }

        return $promotion;
    }

    private function fifoAllocationsForSale(Product $product, int $quantity, ?ProductPromotion $promotion = null): array
    {
        $remainingToConsume = $quantity;
        $allocations = [];

        $batches = StockMovement::where('product_id', $product->id)
            ->sellableBatches()
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToConsume <= 0) {
                break;
            }

            $taken = min($remainingToConsume, $batch->remaining_quantity);
            $originalSellingPrice = (float) ($batch->selling_price ?? $product->selling_price);
            $sellingPrice = $promotion ? (float) $promotion->promotion_price : $originalSellingPrice;
            $purchasePrice = (float) ($batch->purchase_price ?? $product->purchase_price);
            $discountAmount = max(0, $originalSellingPrice - $sellingPrice) * $taken;

            $allocations[] = [
                'batch' => $batch,
                'batch_code' => $batch->batch_code ?? 'LOT-'.$batch->id,
                'quantity' => $taken,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'original_selling_price' => $promotion ? $originalSellingPrice : null,
                'subtotal' => $taken * $sellingPrice,
                'discount_amount' => $discountAmount,
                'promotion_id' => $promotion?->id,
                'promotion_snapshot' => $promotion ? [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'promotion_price' => $sellingPrice,
                    'min_quantity' => $promotion->min_quantity,
                    'original_unit_price' => $originalSellingPrice,
                    'discount_amount' => $discountAmount,
                ] : null,
                'promotion_reason' => $promotion ? " | Promotion: {$promotion->name}" : '',
            ];

            $remainingToConsume -= $taken;
        }

        if ($remainingToConsume > 0) {
            $originalSellingPrice = (float) $product->selling_price;
            $sellingPrice = $promotion ? (float) $promotion->promotion_price : $originalSellingPrice;
            $purchasePrice = (float) $product->purchase_price;
            $discountAmount = max(0, $originalSellingPrice - $sellingPrice) * $remainingToConsume;

            $allocations[] = [
                'batch' => null,
                'batch_code' => 'Stock initial',
                'quantity' => $remainingToConsume,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'original_selling_price' => $promotion ? $originalSellingPrice : null,
                'subtotal' => $remainingToConsume * $sellingPrice,
                'discount_amount' => $discountAmount,
                'promotion_id' => $promotion?->id,
                'promotion_snapshot' => $promotion ? [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'promotion_price' => $sellingPrice,
                    'min_quantity' => $promotion->min_quantity,
                    'original_unit_price' => $originalSellingPrice,
                    'discount_amount' => $discountAmount,
                ] : null,
                'promotion_reason' => $promotion ? " | Promotion: {$promotion->name}" : '',
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
            ->with(['items.product', 'items.promotion', 'paymentTransactions']);

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

        if ($request->filled('product_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('products.id', $request->integer('product_id'))
                    ->where('products.created_by', auth()->user()->created_by);
            });
        } elseif ($request->filled('product_search')) {
            $productSearch = trim((string) $request->product_search);
            $barcodeSearch = preg_replace('/\D+/', '', $productSearch);

            $query->where(function ($saleQuery) use ($productSearch, $barcodeSearch) {
                $saleQuery->whereHas('items.product', function ($q) use ($productSearch, $barcodeSearch) {
                    $q->where('name', 'like', '%'.$productSearch.'%')
                        ->orWhere('barcode', 'like', '%'.$productSearch.'%');

                    if ($barcodeSearch !== '') {
                        $q->orWhere('barcode', 'like', '%'.$barcodeSearch.'%');
                    }
                })->orWhereHas('items', function ($q) use ($productSearch) {
                    $q->where('service_name', 'like', '%'.$productSearch.'%');
                });
            });
        }

        $sales = $query->latest()->paginate(15)->withQueryString();
        $productSuggestions = Product::where('created_by', auth()->user()->created_by)
            ->orderBy('name')
            ->get(['id', 'name', 'barcode'])
            ->push((object) [
                'id' => '',
                'name' => 'Token Energie',
                'barcode' => null,
            ]);

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

        return view('seller.sales.history', compact('sales', 'stats', 'productSuggestions'));
    }

    /**
     * Afficher les détails d'une vente
     */
    public function showSale(Sale $sale)
    {
        // Vérifier que la vente appartient au vendeur
        $this->authorize('view', $sale);

        $sale->load(['items.product', 'items.promotion', 'seller', 'paymentTransactions']);

        return view('seller.sales.show', compact('sale'));
    }

    /**
     * Imprimer le reçu d'une vente
     */
    public function printReceipt(Sale $sale)
    {
        // Vérifier que la vente appartient au vendeur
        $this->authorize('printReceipt', $sale);

        $sale->load(['items.product', 'items.promotion', 'seller', 'paymentTransactions']);

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
