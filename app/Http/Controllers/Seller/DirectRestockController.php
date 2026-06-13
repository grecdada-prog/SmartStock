<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPromotion;
use App\Services\CashRegisterService;
use App\Services\StockRestockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DirectRestockController extends Controller
{
    public function create()
    {
        return view('seller.direct-restock.create');
    }

    public function products(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $barcodeSearch = preg_replace('/\D+/', '', $search);

        if (mb_strlen($search) < 2 && strlen($barcodeSearch) < 3) {
            return response()->json(['products' => []]);
        }

        $products = $this->eligibleProductsQuery()
            ->with([
                'category',
                'promotions' => fn ($query) => $query
                    ->where('manager_id', auth()->user()->created_by)
                    ->orderBy('min_quantity')
                    ->orderBy('created_at')
                    ->orderBy('id'),
                'stockMovements' => fn ($query) => $query
                    ->sellableBatches()
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->where(function ($query) use ($search, $barcodeSearch) {
                $query->where('name', 'like', $search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', $search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%');

                if ($barcodeSearch !== '') {
                    $query->orWhereRaw("REPLACE(barcode, ' ', '') = ?", [$barcodeSearch])
                        ->orWhereRaw("REPLACE(barcode, ' ', '') like ?", [$barcodeSearch.'%'])
                        ->orWhereRaw("REPLACE(barcode, ' ', '') like ?", ['%'.$barcodeSearch.'%']);
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json([
            'products' => $products->map(fn (Product $product) => $this->productSearchPayload($product))->values(),
        ]);
    }

    public function store(Request $request, StockRestockService $restocks, CashRegisterService $cashRegister)
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
            'cash_type' => ['required', 'in:cash,mobile_money'],
        ], [
            'selling_price.gte' => 'Le prix de vente doit etre superieur ou egal au prix d achat.',
            'expiration_date.required_unless' => 'La date de peremption est obligatoire sauf si le produit est non perissable.',
            'expiration_date.after_or_equal' => 'La date de peremption doit etre aujourd hui ou une date future.',
            'promotion_prices.*.min' => 'Le montant promotion doit etre superieur a 0.',
            'cash_type.required' => 'Veuillez choisir une caisse pour payer le livreur.',
            'cash_type.in' => 'Type de caisse invalide.',
        ]);

        $product = $this->eligibleProductsQuery()->findOrFail($validated['product_id']);
        $totalCost = (float) $validated['quantity'] * (float) $validated['purchase_price'];
        $cashType = $validated['cash_type'];
        $referenceId = (string) Str::uuid();
        $manager = auth()->user()->creator ?? auth()->user();

        try {
            DB::transaction(function () use ($restocks, $cashRegister, $product, $validated, $totalCost, $cashType, $referenceId, $manager) {
                if ($totalCost > 0) {
                    $cashRegister->checkBalance(auth()->user(), $cashType, $totalCost);
                }

                $restocks->restock($product, auth()->user(), $validated, 'Appro direct');

                if ($totalCost > 0) {
                    $cashRegister->adjustBalance(
                        auth()->user(),
                        $manager,
                        'withdraw',
                        $totalCost,
                        "Paiement livreur - Appro direct {$product->name} ({$validated['quantity']} unites)",
                        $cashType,
                        'restock',
                        $referenceId
                    );
                }
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['cash_type' => $e->getMessage()]);
        }

        return redirect()->route('seller.direct-restock.create')
            ->with('success', "Stock approvisionne avec succes pour {$product->name} !");
    }

    private function eligibleProductsQuery()
    {
        return Product::query()
            ->where('created_by', auth()->user()->created_by)
            ->where('is_active', true)
            ->where('is_direct_restock_eligible', true);
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
            'promotions' => $product->promotions->map(fn (ProductPromotion $promotion) => [
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
}
