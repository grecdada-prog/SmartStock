<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductPromotion;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockRestockService
{
    public function restock(Product $product, User $actor, array $validated, string $reason = 'Reapprovisionnement'): StockMovement
    {
        return DB::transaction(function () use ($product, $actor, $validated, $reason) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $quantityBefore = $product->quantity;
            $quantityAfter = $quantityBefore + $validated['quantity'];

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $validated['quantity'],
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'purchase_price' => $validated['purchase_price'],
                'selling_price' => $validated['selling_price'],
                'remaining_quantity' => $validated['quantity'],
                'reference' => null,
                'is_perishable' => ! (bool) ($validated['non_perishable'] ?? false),
                'expiration_date' => ($validated['non_perishable'] ?? false) ? null : $validated['expiration_date'],
                'reason' => $reason,
                'user_id' => $actor->id,
            ]);

            $movement->update([
                'batch_code' => 'LOT-' . str_pad((string) $movement->id, 6, '0', STR_PAD_LEFT),
            ]);

            $product->update([
                'quantity' => $quantityAfter,
                'purchase_price' => $validated['purchase_price'],
                'selling_price' => $validated['selling_price'],
            ]);

            $promotionPrices = collect($validated['promotion_prices'] ?? [])
                ->filter(fn ($price) => $price !== null && $price !== '')
                ->mapWithKeys(fn ($price, $promotionId) => [(int) $promotionId => (float) $price]);

            if ($promotionPrices->isNotEmpty()) {
                ProductPromotion::where('manager_id', $product->created_by)
                    ->where('product_id', $product->id)
                    ->whereIn('id', $promotionPrices->keys())
                    ->get()
                    ->each(function (ProductPromotion $promotion) use ($promotionPrices) {
                        $promotion->update([
                            'promotion_price' => $promotionPrices->get($promotion->id),
                        ]);
                    });
            }

            ActivityLog::log(
                $reason === 'Appro direct' ? 'stock_direct_restock' : 'stock_restock',
                "{$reason} de {$product->name} : +{$validated['quantity']} {$product->unit}",
                'Product',
                $product->id
            );

            return $movement->fresh(['product', 'user']);
        });
    }
}
