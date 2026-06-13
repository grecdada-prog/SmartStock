<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashBalanceAdjustment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SaleCancellationService
{
    public function cancelForManager(Sale $sale, User $manager): void
    {
        DB::transaction(function () use ($sale, $manager) {
            $lockedSale = Sale::with(['items.product', 'paymentTransactions', 'seller'])
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedSale->seller?->created_by !== (int) $manager->id) {
                throw new AuthorizationException('Cette vente ne vous appartient pas.');
            }

            $invoiceNumber = $lockedSale->invoice_number;
            $seller = $lockedSale->seller;

            $lockedSale->items
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->each(function ($items) use ($lockedSale, $seller, $manager, $invoiceNumber) {
                    $firstItem = $items->first();
                    $quantity = (int) $items->sum('quantity');

                    if ($quantity <= 0 || ! $firstItem->product_id) {
                        return;
                    }

                    $product = Product::whereKey($firstItem->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $product) {
                        return;
                    }

                    $quantityBefore = (int) $product->quantity;
                    $quantityAfter = $quantityBefore + $quantity;
                    $unitPrice = $quantity > 0 ? (float) $items->sum('subtotal') / $quantity : (float) $firstItem->unit_price;

                    $product->update([
                        'quantity' => $quantityAfter,
                    ]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $manager->id,
                        'type' => StockMovement::TYPE_CORRECTION_CANCELLATION,
                        'quantity' => $quantity,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityAfter,
                        'purchase_price' => $product->purchase_price,
                        'selling_price' => $unitPrice,
                        'remaining_quantity' => $quantity,
                        'batch_code' => 'ANN-'.$invoiceNumber.'-'.$product->id,
                        'reference' => 'Annulation vente #'.$invoiceNumber,
                        'reason' => 'Correction annulation par '.$manager->name.' - vendeur '.$seller->name,
                    ]);
                });

            CashBalanceAdjustment::create([
                'seller_id' => $seller->id,
                'manager_id' => $manager->id,
                'type' => 'correction_cancellation',
                'balance_type' => $this->balanceTypeForPaymentMethod((string) $lockedSale->payment_method),
                'amount' => $lockedSale->total,
                'reason' => 'Correction annulation vente #'.$invoiceNumber,
            ]);

            foreach ($lockedSale->paymentTransactions as $transaction) {
                $transaction->update([
                    'failure_reason' => trim(($transaction->failure_reason ? $transaction->failure_reason.' | ' : '').'Vente supprimee par le gerant '.$manager->name),
                ]);
            }

            ActivityLog::log(
                'sale_cancelled',
                'Vente supprimee par le gerant : #'.$invoiceNumber.' - '.number_format((float) $lockedSale->total, 0, ',', ' ').' FCFA',
                'Sale',
                $lockedSale->id,
                [
                    'invoice_number' => $invoiceNumber,
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'manager_id' => $manager->id,
                    'manager_name' => $manager->name,
                    'payment_method' => $lockedSale->payment_method,
                    'total' => (float) $lockedSale->total,
                ]
            );

            $lockedSale->delete();
        });
    }

    private function balanceTypeForPaymentMethod(string $paymentMethod): string
    {
        return in_array($paymentMethod, [
            CashRegisterService::ORANGE_MONEY_PAYMENT_METHOD,
            CashRegisterService::MTN_MOMO_PAYMENT_METHOD,
        ], true)
            ? CashRegisterService::MOBILE_MONEY_BALANCE_TYPE
            : CashRegisterService::CASH_BALANCE_TYPE;
    }
}
