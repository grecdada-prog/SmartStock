<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductPromotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductPromotionController extends Controller
{
    public function index()
    {
        $promotions = ProductPromotion::with(['product.category'])
            ->where('manager_id', auth()->id())
            ->latest()
            ->get();

        return view('manager.promotions.index', compact('promotions'));
    }

    public function create()
    {
        return view('manager.promotions.create', [
            'products' => $this->promotionProducts(),
            'promotion' => new ProductPromotion([
                'min_quantity' => 2,
                'status' => ProductPromotion::STATUS_ACTIVE,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPromotionData($request);
        $product = $this->managerProduct((int) $validated['product_id']);

        $this->ensurePromotionPriceIsBelowCurrentPrice($product, (float) $validated['promotion_price']);

        if (($validated['status'] ?? ProductPromotion::STATUS_ACTIVE) === ProductPromotion::STATUS_ACTIVE) {
            $this->suspendOtherActivePromotions($product->id);
        }

        $promotion = ProductPromotion::create([
            ...$validated,
            'manager_id' => auth()->id(),
            'status' => $validated['status'] ?? ProductPromotion::STATUS_ACTIVE,
        ]);

        ActivityLog::log(
            'promotion_created',
            "Promotion creee : {$promotion->name} sur {$product->name}",
            'ProductPromotion',
            $promotion->id
        );

        return redirect()->route('manager.promotions.index')
            ->with('success', 'Promotion enregistree avec succes.');
    }

    public function edit(ProductPromotion $promotion)
    {
        $this->authorizeManagerPromotion($promotion);

        return view('manager.promotions.edit', [
            'promotion' => $promotion->load('product.stockMovements'),
            'products' => $this->promotionProducts(),
        ]);
    }

    public function update(Request $request, ProductPromotion $promotion)
    {
        $this->authorizeManagerPromotion($promotion);

        $validated = $this->validatedPromotionData($request, $promotion);
        $product = $this->managerProduct((int) $validated['product_id']);

        $this->ensurePromotionPriceIsBelowCurrentPrice($product, (float) $validated['promotion_price']);

        if (($validated['status'] ?? $promotion->status) === ProductPromotion::STATUS_ACTIVE) {
            $this->suspendOtherActivePromotions($product->id, $promotion->id);
        }

        $promotion->update([
            ...$validated,
            'status' => $validated['status'] ?? ProductPromotion::STATUS_ACTIVE,
        ]);

        ActivityLog::log(
            'promotion_updated',
            "Promotion mise a jour : {$promotion->name}",
            'ProductPromotion',
            $promotion->id
        );

        return redirect()->route('manager.promotions.index')
            ->with('success', 'Promotion mise a jour avec succes.');
    }

    public function destroy(ProductPromotion $promotion)
    {
        $this->authorizeManagerPromotion($promotion);

        $promotionName = $promotion->name;
        $promotion->delete();

        ActivityLog::log(
            'promotion_deleted',
            "Promotion supprimee : {$promotionName}",
            'ProductPromotion'
        );

        return redirect()->route('manager.promotions.index')
            ->with('success', 'Promotion supprimee avec succes.');
    }

    public function toggleStatus(ProductPromotion $promotion)
    {
        $this->authorizeManagerPromotion($promotion);

        $newStatus = $promotion->isActive()
            ? ProductPromotion::STATUS_SUSPENDED
            : ProductPromotion::STATUS_ACTIVE;

        if ($newStatus === ProductPromotion::STATUS_ACTIVE) {
            $this->suspendOtherActivePromotions($promotion->product_id, $promotion->id);
        }

        $promotion->update(['status' => $newStatus]);

        return back()->with('success', $newStatus === ProductPromotion::STATUS_ACTIVE
            ? 'Promotion reactivee avec succes.'
            : 'Promotion suspendue avec succes.');
    }

    private function validatedPromotionData(Request $request, ?ProductPromotion $promotion = null): array
    {
        return $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('created_by', auth()->id())],
            'name' => ['required', 'string', 'max:255'],
            'promotion_price' => ['required', 'numeric', 'min:1'],
            'min_quantity' => ['required', 'integer', 'min:2'],
            'status' => ['required', Rule::in([ProductPromotion::STATUS_ACTIVE, ProductPromotion::STATUS_SUSPENDED])],
        ], [
            'product_id.required' => 'Choisissez un produit.',
            'product_id.exists' => 'Produit introuvable pour ce gerant.',
            'promotion_price.min' => 'Le prix promotionnel doit etre superieur a 0.',
            'min_quantity.min' => 'La promotion doit commencer a partir de 2 articles.',
        ]);
    }

    private function promotionProducts()
    {
        return Product::with([
            'category',
            'stockMovements' => fn ($query) => $query
                ->where('type', 'in')
                ->where('remaining_quantity', '>', 0)
                ->orderBy('created_at')
                ->orderBy('id'),
        ])
            ->where('created_by', auth()->id())
            ->orderBy('name')
            ->get();
    }

    private function managerProduct(int $productId): Product
    {
        return Product::with([
            'stockMovements' => fn ($query) => $query
                ->where('type', 'in')
                ->where('remaining_quantity', '>', 0)
                ->orderBy('created_at')
                ->orderBy('id'),
        ])
            ->where('created_by', auth()->id())
            ->findOrFail($productId);
    }

    private function authorizeManagerPromotion(ProductPromotion $promotion): void
    {
        abort_unless($promotion->manager_id === auth()->id(), 403);
    }

    private function suspendOtherActivePromotions(int $productId, ?int $exceptId = null): void
    {
        ProductPromotion::where('manager_id', auth()->id())
            ->where('product_id', $productId)
            ->where('status', ProductPromotion::STATUS_ACTIVE)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['status' => ProductPromotion::STATUS_SUSPENDED]);
    }

    private function ensurePromotionPriceIsBelowCurrentPrice(Product $product, float $promotionPrice): void
    {
        $currentPrices = $product->stockMovements
            ->pluck('selling_price')
            ->filter(fn ($price) => $price !== null && (float) $price > 0)
            ->map(fn ($price) => (float) $price);

        if ($currentPrices->isEmpty() && (float) $product->selling_price > 0) {
            $currentPrices->push((float) $product->selling_price);
        }

        if ($currentPrices->isNotEmpty() && $promotionPrice >= $currentPrices->max()) {
            throw ValidationException::withMessages([
                'promotion_price' => 'Le prix promotionnel doit etre inferieur au prix de vente actuel.',
            ]);
        }
    }
}
