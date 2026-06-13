<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductPromotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SellerSaleFinalizer
{
    private const SMS_FEE = 25.0;

    public function createProductSale(User $seller, array $validated, ?string $clientSaleToken = null): Sale
    {
        if ($clientSaleToken) {
            $existingSale = Sale::where('seller_id', $seller->id)
                ->where('client_sale_token', $clientSaleToken)
                ->first();

            if ($existingSale) {
                return $existingSale;
            }
        }

        try {
            return DB::transaction(function () use ($seller, $validated, $clientSaleToken) {
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

                foreach ($requestedItems as $item) {
                    $product = Product::where('created_by', $seller->created_by)
                        ->lockForUpdate()
                        ->findOrFail($item['product_id']);

                    if (! $product->is_active) {
                        throw new \Exception("Le produit '{$product->name}' n'est plus disponible.");
                    }

                    if ($product->quantity < $item['quantity']) {
                        throw new \Exception("Stock insuffisant pour '{$product->name}'. Disponible: {$product->quantity}");
                    }

                    $promotion = $this->eligiblePromotionForSale($seller, $product, $item);
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

                $operatorFee = $this->operatorFeeForPayment($validated['payment_method'], $totalAmount);
                $totalToPay = $totalAmount + $operatorFee;

                if (array_key_exists('expected_total', $validated) && abs((float) $validated['expected_total'] - $totalToPay) > 0.01) {
                    throw new \Exception('Le total de la vente a change depuis le lancement du paiement.');
                }

                $amountReceived = $validated['payment_method'] === 'cash'
                    ? ($validated['amount_received'] ?? $totalAmount)
                    : $totalToPay;

                if ($validated['payment_method'] === 'cash' && $amountReceived < $totalAmount) {
                    throw new \Exception('Le montant recu doit couvrir le total de la vente.');
                }

                $sale = Sale::create([
                    'seller_id' => $seller->id,
                    'invoice_number' => Sale::generateInvoiceNumber(),
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

                foreach ($itemsData as $itemData) {
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

                        StockMovement::create([
                            'product_id' => $itemData['product']->id,
                            'user_id' => $seller->id,
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

                ActivityLog::log(
                    'sale_created',
                    "Vente creee : #{$sale->invoice_number} - Total: ".number_format($totalToPay, 0, ',', ' ').' FCFA',
                    'Sale',
                    $sale->id,
                    [
                        'invoice_number' => $sale->invoice_number,
                        'seller_id' => $seller->id,
                        'subtotal' => $totalAmount,
                        'operator_fee' => $operatorFee,
                        'total' => $totalToPay,
                        'items_count' => count($itemsData),
                        'payment_method' => $validated['payment_method'],
                    ]
                );

                return $sale;
            });
        } catch (QueryException $e) {
            if ($clientSaleToken && $this->isDuplicateClientTokenError($e)) {
                $existingSale = Sale::where('seller_id', $seller->id)
                    ->where('client_sale_token', $clientSaleToken)
                    ->first();

                if ($existingSale) {
                    return $existingSale;
                }
            }

            throw $e;
        }
    }

    public function createEnergyTokenSale(User $seller, array $validated, array $apiResponse): Sale
    {
        return DB::transaction(function () use ($seller, $validated, $apiResponse) {
            $amount = (float) $validated['amount'];
            $sendSms = filter_var($validated['send_sms'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $smsFee = $sendSms ? self::SMS_FEE : 0.0;
            $operatorFee = $this->operatorFeeForPayment($validated['payment_method'], $amount, 0.03);
            $totalToPay = $amount + $operatorFee + $smsFee;
            $kwh = round($amount / 120, 2);
            $smsNote = $sendSms ? ' | SMS: '.$validated['sms_phone'].' | Frais SMS: '.number_format($smsFee, 0, ',', ' ').' FCFA' : '';

            if (array_key_exists('expected_total', $validated) && abs((float) $validated['expected_total'] - $totalToPay) > 0.01) {
                throw new \Exception('Le total du token a change depuis le lancement du paiement.');
            }

            $sale = Sale::create([
                'seller_id' => $seller->id,
                'invoice_number' => Sale::generateInvoiceNumber(),
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
                    'token_transaction' => $validated['token_transaction'] ?? null,
                    'send_sms' => $sendSms,
                    'sms_phone' => $sendSms ? $validated['sms_phone'] : null,
                    'sms_fee' => $smsFee,
                    'sms_status' => $sendSms ? 'pending' : null,
                    'operator_fee' => $operatorFee,
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
                    'seller_id' => $seller->id,
                    'meter_address' => $validated['meter_address'],
                    'room_number' => $validated['room_number'],
                    'kwh' => $kwh,
                    'token' => $apiResponse['token'],
                    'token_transaction' => $validated['token_transaction'] ?? null,
                    'subtotal' => $amount,
                    'operator_fee' => $operatorFee,
                    'send_sms' => $sendSms,
                    'sms_phone' => $sendSms ? $validated['sms_phone'] : null,
                    'sms_fee' => $smsFee,
                    'total' => $totalToPay,
                    'payment_method' => $validated['payment_method'],
                ]
            );

            return $sale;
        });
    }

    public function operatorFeeForPayment(string $paymentMethod, float $amount, float $feeRate = 0.02): float
    {
        return $paymentMethod === 'cash' ? 0.0 : (float) round($amount * $feeRate);
    }

    private function eligiblePromotionForSale(User $seller, Product $product, array $item): ?ProductPromotion
    {
        if (! ($item['apply_promotion'] ?? false)) {
            return null;
        }

        $promotionId = $item['promotion_id'] ?? null;

        $promotion = ProductPromotion::where('manager_id', $seller->created_by)
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

    private function isDuplicateClientTokenError(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'client_sale_token')
            || str_contains($message, 'sales_client_sale_token_unique')
            || (str_contains($message, 'UNIQUE') && str_contains($message, 'sales'));
    }
}
