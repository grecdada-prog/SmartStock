<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductPromotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductPromotionController extends Controller
{
    public function index()
    {
        $promotions = ProductPromotion::with(['product.category'])
            ->where('manager_id', auth()->id())
            ->latest()
            ->paginate(15)->withQueryString();

        return view('manager.promotions.index', compact('promotions'));
    }

    public function create(Request $request)
    {
        $productId = $request->integer('product_id');
        $selectedProductId = $productId && Product::where('created_by', auth()->id())->whereKey($productId)->exists()
            ? $productId
            : null;

        return view('manager.promotions.create', [
            'products' => $this->promotionProducts($selectedProductId),
            'promotion' => new ProductPromotion([
                'product_id' => $selectedProductId,
                'min_quantity' => 1,
                'status' => ProductPromotion::STATUS_ACTIVE,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPromotionData($request);
        $product = $this->managerProduct((int) $validated['product_id']);

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
            'products' => $this->promotionProducts($promotion->product_id),
        ]);
    }

    public function update(Request $request, ProductPromotion $promotion)
    {
        $this->authorizeManagerPromotion($promotion);

        $validated = $this->validatedPromotionData($request, $promotion);
        $product = $this->managerProduct((int) $validated['product_id']);

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

    public function destroy(Request $request, ProductPromotion $promotion)
    {
        $this->authorizeManagerPromotion($promotion);

        $promotionName = $promotion->name;
        $promotion->delete();

        ActivityLog::log(
            'promotion_deleted',
            "Promotion supprimee : {$promotionName}",
            'ProductPromotion'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Promotion supprimee avec succes.',
            ]);
        }

        return redirect()->route('manager.promotions.index')
            ->with('success', 'Promotion supprimee avec succes.');
    }

    public function toggleStatus(ProductPromotion $promotion)
    {
        $this->authorizeManagerPromotion($promotion);

        $newStatus = $promotion->isActive()
            ? ProductPromotion::STATUS_SUSPENDED
            : ProductPromotion::STATUS_ACTIVE;

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
            'min_quantity' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in([ProductPromotion::STATUS_ACTIVE, ProductPromotion::STATUS_SUSPENDED])],
        ], [
            'product_id.required' => 'Choisissez un produit.',
            'product_id.exists' => 'Produit introuvable pour ce gerant.',
            'promotion_price.min' => 'Le prix promotionnel doit etre superieur a 0.',
            'min_quantity.min' => 'La promotion doit commencer a partir de 1 article.',
        ]);
    }

    private function promotionProducts(?int $selectedProductId = null)
    {
        if (! $selectedProductId) {
            return collect();
        }

        return Product::with([
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
            ->whereKey($selectedProductId)
            ->orderBy('name')
            ->get();
    }

    private function managerProduct(int $productId): Product
    {
        return Product::with([
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
            ->findOrFail($productId);
    }

    private function authorizeManagerPromotion(ProductPromotion $promotion): void
    {
        abort_unless($promotion->manager_id === auth()->id(), 403);
    }

}
