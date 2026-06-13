<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Services\AvlyTextSmsService;
use App\Services\CashRegisterService;
use App\Services\EnergyTokenRecentSaleService;
use App\Services\MonetbilPaymentService;
use App\Services\SellerSaleFinalizer;
use App\Services\SocadelTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MonetbilPaymentController extends Controller
{
    private const SMS_FEE = 25.0;

    public function startSellerPosPayment(Request $request, MonetbilPaymentService $monetbil)
    {
        $seller = auth()->user();

        if (! app(CashRegisterService::class)->isOpenForSeller($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'La caisse n\'est pas ouverte. Ouvrez la caisse depuis le dashboard avant de commencer les ventes.',
            ], 423);
        }

        $validated = $this->validatePosPayload($request);
        $operator = $this->operatorOrFail($validated['customer_phone'] ?? null, $monetbil);
        $validated['payment_method'] = $operator['key'] === 'orange_money' ? 'card' : 'mobile_money';

        [$amount, $fee, $total] = $this->quotePosSale($seller->created_by, $validated);

        $transaction = PaymentTransaction::create([
            'reference' => PaymentTransaction::generateReference(),
            'type' => PaymentTransaction::TYPE_POS_SALE,
            'status' => PaymentTransaction::STATUS_PENDING,
            'seller_id' => $seller->id,
            'manager_id' => $seller->created_by,
            'payment_method' => $validated['payment_method'],
            'operator_code' => $operator['code'],
            'operator_label' => $operator['label'],
            'customer_phone' => $monetbil->localCameroonDigits($validated['customer_phone']),
            'amount' => $amount,
            'operator_fee' => $fee,
            'total_amount' => $total,
            'currency' => config('services.monetbil.currency', 'XAF'),
            'country' => config('services.monetbil.country', 'CM'),
            'sale_payload' => $validated,
            'expires_at' => now()->addMinutes(3),
        ]);

        $monetbil->placePayment($transaction);

        return $this->transactionResponse($transaction->fresh());
    }

    public function startSellerTokenPayment(Request $request, MonetbilPaymentService $monetbil)
    {
        $seller = auth()->user();

        if (! app(CashRegisterService::class)->isOpenForSeller($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'La caisse n\'est pas ouverte. Ouvrez la caisse depuis le dashboard avant de vendre un token.',
            ], 423);
        }

        $request->merge([
            'room_number' => Str::upper(preg_replace('/\s+/', '', (string) $request->input('room_number'))),
        ]);
        $this->normalizeContactInputs($request, ['customer_phone', 'sms_phone'], []);

        $validated = $request->validate([
            'meter_address' => ['required', 'string', 'in:'.implode(',', $this->tokenMeterAddresses())],
            'room_number' => ['required', 'string', 'in:'.implode(',', $this->tokenRooms())],
            'amount' => ['required', 'numeric', 'min:240', $this->tokenAmountMultipleRule()],
            'payment_method' => ['required', 'in:mobile_money'],
            'customer_phone' => ['required', ...$this->phoneRules()],
            'send_sms' => ['nullable', 'boolean'],
            'sms_phone' => ['required_if:send_sms,1', ...$this->phoneRules()],
            'confirmed_recent_token' => ['nullable', 'boolean'],
        ], [
            'sms_phone.required_if' => 'Le numero SMS est obligatoire pour recevoir le token par SMS.',
            'sms_phone.regex' => 'Le numero SMS doit contenir 9 a 15 chiffres.',
        ]);
        $validated['send_sms'] = $request->boolean('send_sms');
        $validated['sms_phone'] = $validated['send_sms'] ? ($validated['sms_phone'] ?? null) : null;
        $validated['confirmed_recent_token'] = $request->boolean('confirmed_recent_token');

        if (! $validated['confirmed_recent_token']) {
            $recentSale = app(EnergyTokenRecentSaleService::class)->find($validated['meter_address'], $validated['room_number']);

            if ($recentSale) {
                return response()->json([
                    'success' => false,
                    'recent' => true,
                    'message' => app(EnergyTokenRecentSaleService::class)
                        ->messageFor($recentSale, $validated['meter_address'], $validated['room_number']),
                ], 409);
            }
        }

        $operator = $this->operatorOrFail($validated['customer_phone'] ?? null, $monetbil);
        $validated['payment_method'] = $operator['key'] === 'orange_money' ? 'card' : 'mobile_money';

        $amount = (float) $validated['amount'];
        $operatorFee = (float) round($amount * 0.03);
        $smsFee = $validated['send_sms'] ? self::SMS_FEE : 0.0;
        $fee = $operatorFee + $smsFee;
        $total = $amount + $fee;

        $transaction = PaymentTransaction::create([
            'reference' => PaymentTransaction::generateReference(),
            'type' => PaymentTransaction::TYPE_ENERGY_TOKEN,
            'status' => PaymentTransaction::STATUS_PENDING,
            'seller_id' => $seller->id,
            'manager_id' => $seller->created_by,
            'payment_method' => $validated['payment_method'],
            'operator_code' => $operator['code'],
            'operator_label' => $operator['label'],
            'customer_phone' => $monetbil->localCameroonDigits($validated['customer_phone']),
            'amount' => $amount,
            'operator_fee' => $fee,
            'total_amount' => $total,
            'currency' => config('services.monetbil.currency', 'XAF'),
            'country' => config('services.monetbil.country', 'CM'),
            'sale_payload' => $validated,
            'expires_at' => now()->addMinutes(3),
        ]);

        $monetbil->placePayment($transaction);

        return $this->transactionResponse($transaction->fresh());
    }

    public function checkSellerPayment(PaymentTransaction $transaction, MonetbilPaymentService $monetbil, SellerSaleFinalizer $finalizer)
    {
        abort_unless($transaction->seller_id === auth()->id(), 403);

        return $this->checkAndFinalize($transaction, $monetbil, $finalizer);
    }

    public function checkManagerPayment(PaymentTransaction $transaction, MonetbilPaymentService $monetbil, SellerSaleFinalizer $finalizer)
    {
        abort_unless($transaction->manager_id === auth()->id(), 403);

        return $this->checkAndFinalize($transaction, $monetbil, $finalizer);
    }

    public function callback(Request $request, MonetbilPaymentService $monetbil, SellerSaleFinalizer $finalizer)
    {
        $payload = $request->all();
        $paymentId = data_get($payload, 'paymentId') ?? data_get($payload, 'payment_id');
        $reference = data_get($payload, 'payment_ref') ?? data_get($payload, 'transaction.payment_ref');

        if (! $paymentId && ! $reference) {
            return response()->json(['success' => false, 'message' => 'Reference paiement absente.'], 422);
        }

        $transaction = PaymentTransaction::query()
            ->when($paymentId, fn ($query) => $query->orWhere('monetbil_payment_id', $paymentId))
            ->when($reference, fn ($query) => $query->orWhere('reference', $reference))
            ->first();

        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction introuvable.'], 404);
        }

        $transaction->update(['callback_payload' => $payload]);

        return $this->checkAndFinalize($transaction, $monetbil, $finalizer);
    }

    public function showForManager(PaymentTransaction $transaction)
    {
        abort_unless($transaction->manager_id === auth()->id(), 403);

        $transaction->load(['seller', 'sale.items.product']);

        return view('payment-transactions.show', compact('transaction'));
    }

    public function showForSuperAdmin(PaymentTransaction $transaction)
    {
        $transaction->load(['seller', 'manager', 'sale.items.product']);

        return view('payment-transactions.show', compact('transaction'));
    }

    private function checkAndFinalize(PaymentTransaction $transaction, MonetbilPaymentService $monetbil, SellerSaleFinalizer $finalizer)
    {
        return DB::transaction(function () use ($transaction, $monetbil, $finalizer) {
        $transaction = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

        if ($transaction->sale_id && $transaction->status === PaymentTransaction::STATUS_SUCCESS) {
            return $this->transactionResponse($transaction);
        }

        $response = $monetbil->checkPayment($transaction);
        $transaction = $transaction->fresh();

        if ($monetbil->transactionFailed($response)) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failed_at' => now(),
                'failure_reason' => $this->readableMonetbilMessage(data_get($response, 'transaction.message', 'Paiement refuse par Monetbil.')),
            ]);

            return $this->transactionResponse($transaction->fresh());
        }

        if (! $monetbil->transactionSucceeded($response)) {
            if ($transaction->status === PaymentTransaction::STATUS_PENDING && $transaction->isExpired()) {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_EXPIRED,
                    'failed_at' => now(),
                    'failure_reason' => 'Transaction expiree apres 3 minutes.',
                ]);

                return $this->transactionResponse($transaction->fresh());
            }

            return $this->transactionResponse($transaction);
        }

        try {
            $salePayload = $transaction->sale_payload ?? [];
            $salePayload['expected_total'] = (float) $transaction->total_amount;
            $seller = $transaction->seller;

            if ($transaction->type === PaymentTransaction::TYPE_ENERGY_TOKEN) {
                $amount = (float) $salePayload['amount'];
                if (! filter_var($salePayload['confirmed_recent_token'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $recentSale = app(EnergyTokenRecentSaleService::class)
                        ->find($salePayload['meter_address'], $salePayload['room_number']);

                    if ($recentSale) {
                        throw new \Exception(app(EnergyTokenRecentSaleService::class)
                            ->messageFor($recentSale, $salePayload['meter_address'], $salePayload['room_number']));
                    }
                }
                $socadelTokenService = app(SocadelTokenService::class);
                $tokenTransactionReference = $socadelTokenService->generateReference();
                $apiResponse = $socadelTokenService->sell(
                    $salePayload['meter_address'],
                    $salePayload['room_number'],
                    $amount,
                    $tokenTransactionReference
                );
                $salePayload['token_transaction'] = $tokenTransactionReference;
                $sale = $finalizer->createEnergyTokenSale($seller, $salePayload, $apiResponse);
                app(AvlyTextSmsService::class)->sendEnergyTokenSms($sale->load('items'));
            } else {
                $sale = $finalizer->createProductSale($seller, $salePayload, $salePayload['client_sale_token'] ?? null);
            }

            $transaction->update([
                'status' => PaymentTransaction::STATUS_SUCCESS,
                'sale_id' => $sale->id,
                'confirmed_at' => now(),
                'failure_reason' => null,
            ]);
        } catch (\Throwable $e) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_PAID_ACTION_REQUIRED,
                'confirmed_at' => now(),
                'failure_reason' => $e->getMessage(),
            ]);
        }

        return $this->transactionResponse($transaction->fresh());
        });
    }

    private function validatePosPayload(Request $request): array
    {
        return $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.apply_promotion' => ['nullable', 'boolean'],
            'items.*.promotion_id' => ['nullable', 'exists:product_promotions,id'],
            'payment_method' => ['required', 'in:mobile_money'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['required', 'regex:/^[0-9\\s+().-]{9,20}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'client_sale_token' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function tokenAmountMultipleRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (abs(fmod((float) $value, 120.0)) > 0.00001) {
                $fail('montant invalide. Veuillez saisir un multiple de 120.');
            }
        };
    }

    private function quotePosSale(int $managerId, array $validated): array
    {
        $amount = 0.0;

        $requestedItems = collect($validated['items'])
            ->groupBy('product_id')
            ->map(fn ($items, $productId) => [
                'product_id' => (int) $productId,
                'quantity' => (int) $items->sum('quantity'),
                'promotion_id' => $items->firstWhere('apply_promotion', true)['promotion_id'] ?? null,
                'apply_promotion' => (bool) $items->contains(fn ($item) => (bool) ($item['apply_promotion'] ?? false)),
            ]);

        foreach ($requestedItems as $item) {
            $product = Product::where('created_by', $managerId)->findOrFail($item['product_id']);

            if (! $product->is_active || $product->quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => "Stock insuffisant pour '{$product->name}'. Disponible: {$product->quantity}",
                ]);
            }

            $promotion = null;

            if ($item['apply_promotion']) {
                $promotion = $product->promotions()
                    ->where('manager_id', $managerId)
                    ->where('status', 'active')
                    ->where('id', $item['promotion_id'])
                    ->first();

                if (! $promotion || $item['quantity'] < $promotion->min_quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Promotion non applicable pour '{$product->name}'.",
                    ]);
                }
            }

            $amount += $this->quoteProductLine($product, $item['quantity'], $promotion?->promotion_price);
        }

        $fee = (float) round($amount * 0.02);

        return [$amount, $fee, $amount + $fee];
    }

    private function quoteProductLine(Product $product, int $quantity, ?float $promotionPrice): float
    {
        if ($promotionPrice !== null) {
            return $quantity * $promotionPrice;
        }

        $remaining = $quantity;
        $total = 0.0;

        foreach ($product->stockMovements()->sellableBatches()->orderBy('created_at')->orderBy('id')->get() as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $taken = min($remaining, $batch->remaining_quantity);
            $total += $taken * (float) ($batch->selling_price ?? $product->selling_price);
            $remaining -= $taken;
        }

        if ($remaining > 0) {
            $total += $remaining * (float) $product->selling_price;
        }

        return $total;
    }

    private function operatorOrFail(?string $phone, MonetbilPaymentService $monetbil): array
    {
        $operator = $monetbil->operatorFromPhone($phone);

        if (! $operator) {
            throw ValidationException::withMessages([
                'customer_phone' => 'Numero non reconnu pour Orange Money ou MTN Momo Cameroun.',
            ]);
        }

        return $operator;
    }

    private function transactionResponse(PaymentTransaction $transaction)
    {
        $tokenSale = null;

        if ($transaction->type === PaymentTransaction::TYPE_ENERGY_TOKEN && $transaction->sale) {
            $sale = $transaction->sale;
            $item = $sale->items()->first();
            $payload = $item?->service_payload ?? [];

            $tokenSale = [
                'token' => $payload['token'] ?? null,
                'address' => $payload['meter_address'] ?? null,
                'room' => $payload['room_number'] ?? null,
                'amount' => (float) ($payload['token_amount'] ?? $item?->unit_price ?? $transaction->amount),
                'operator_fee' => (float) ($payload['operator_fee'] ?? $transaction->operator_fee),
                'sms_fee' => (float) ($payload['sms_fee'] ?? 0),
                'total' => (float) ($payload['total_to_pay'] ?? $sale->total),
                'kwh' => (float) ($payload['kwh'] ?? 0),
                'send_sms' => (bool) ($payload['send_sms'] ?? false),
                'sms_phone' => $payload['sms_phone'] ?? null,
                'payment_method' => $sale->payment_method_label,
            ];
        }

        return response()->json([
            'success' => ! in_array($transaction->status, [PaymentTransaction::STATUS_FAILED, PaymentTransaction::STATUS_EXPIRED], true),
            'status' => $transaction->status,
            'message' => $this->messageForStatus($transaction),
            'transaction' => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'status' => $transaction->status,
                'amount' => (float) $transaction->amount,
                'operator_fee' => (float) $transaction->operator_fee,
                'total_amount' => (float) $transaction->total_amount,
                'operator_label' => $transaction->operator_label,
                'monetbil_payment_id' => $transaction->monetbil_payment_id,
                'expires_at' => $transaction->expires_at?->toIso8601String(),
                'failure_reason' => $transaction->failure_reason,
            ],
            'sale_id' => $transaction->sale_id,
            'invoice_number' => $transaction->sale?->invoice_number,
            'receipt_url' => $transaction->sale_id ? route('seller.pos.receipt', $transaction->sale_id) : null,
            'token_sale' => $tokenSale,
        ]);
    }

    private function messageForStatus(PaymentTransaction $transaction): string
    {
        return match ($transaction->status) {
            PaymentTransaction::STATUS_SUCCESS => 'Paiement confirme. Vente enregistree.',
            PaymentTransaction::STATUS_FAILED => $this->readableMonetbilMessage($transaction->failure_reason ?: 'Paiement refuse.'),
            PaymentTransaction::STATUS_EXPIRED => 'Transaction expiree. Relancez le paiement.',
            PaymentTransaction::STATUS_PAID_ACTION_REQUIRED => 'Paiement confirme, action manuelle requise.',
            default => 'Paiement en attente de confirmation Monetbil.',
        };
    }

    private function readableMonetbilMessage(?string $message): string
    {
        return match ($message) {
            'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED' => 'Solde insuffisant, limite atteinte ou paiement non autorise.',
            default => $message ?: 'Paiement refuse par Monetbil.',
        };
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

    private function tokenMeterAddresses(): array
    {
        return ['SMARTCITY 1', 'NKOZOA'];
    }
}
