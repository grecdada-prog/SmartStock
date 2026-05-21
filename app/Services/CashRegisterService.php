<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashBalanceAdjustment;
use App\Models\CashRegisterClosure;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashRegisterService
{
    private const CASH_PAYMENT_METHOD = 'cash';
    public const ORANGE_MONEY_PAYMENT_METHOD = 'card';
    public const MTN_MOMO_PAYMENT_METHOD = 'mobile_money';

    public function closeForSeller(User $seller, ?Carbon $date = null, string $closedBy = 'manual', ?string $reason = null): CashRegisterClosure
    {
        $businessDate = ($date ?? today())->toDateString();

        return DB::transaction(function () use ($seller, $businessDate, $closedBy, $reason) {
            $actorId = auth()->id();
            User::whereKey($seller->id)->lockForUpdate()->firstOrFail();

            $existingClosure = CashRegisterClosure::where('seller_id', $seller->id)
                ->whereDate('business_date', $businessDate)
                ->lockForUpdate()
                ->first();

            if ($existingClosure) {
                if ($existingClosure->opened_at === null) {
                    return $existingClosure;
                }

                $baseAmount = $existingClosure->closed_by === 'not_closed'
                    ? 0
                    : $this->cashRevenueForClosure($existingClosure);
                $amount = $this->cashRevenueForDate($seller, $businessDate, $existingClosure->opened_at);

                $existingClosure->update($this->closureAuditAttributes([
                    'amount' => $baseAmount + (float) $amount,
                    'closed_by' => $closedBy,
                    'closed_at' => now(),
                    'opened_at' => null,
                ], $actorId, true));

                ActivityLog::log(
                    'cash_register_closed',
                    'Caisse fermee pour le vendeur '.$seller->name.' : '.number_format((float) $amount, 0, ',', ' ').' FCFA',
                    'CashRegisterClosure',
                    $existingClosure->id,
                    [
                        'seller_id' => $seller->id,
                        'seller_name' => $seller->name,
                        'closed_by' => $closedBy,
                        'reason' => $reason,
                        'amount' => (float) $amount,
                    ]
                );

                return $existingClosure;
            }

            $amount = 0;

            $closure = CashRegisterClosure::create($this->closureAuditAttributes([
                'seller_id' => $seller->id,
                'business_date' => $businessDate,
                'amount' => $amount,
                'closed_by' => $closedBy,
                'closed_at' => now(),
            ], $actorId));

            ActivityLog::log(
                'cash_register_closed',
                'Caisse fermee pour le vendeur '.$seller->name.' : '.number_format((float) $amount, 0, ',', ' ').' FCFA',
                'CashRegisterClosure',
                $closure->id,
                [
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'closed_by' => $closedBy,
                    'reason' => $reason,
                    'amount' => (float) $amount,
                ]
            );

            return $closure;
        });
    }

    public function openForSeller(User $seller, ?Carbon $date = null, string $openedBy = 'seller', ?string $reason = null): ?CashRegisterClosure
    {
        return DB::transaction(function () use ($seller, $date, $openedBy, $reason) {
            $actorId = auth()->id();
            $businessDate = ($date ?? today())->toDateString();
            User::whereKey($seller->id)->lockForUpdate()->firstOrFail();

            $closure = CashRegisterClosure::where('seller_id', $seller->id)
                ->whereDate('business_date', $businessDate)
                ->lockForUpdate()
                ->first();

            if (! $closure) {
                $closure = CashRegisterClosure::create($this->openingAuditAttributes([
                    'seller_id' => $seller->id,
                    'business_date' => $businessDate,
                    'amount' => 0,
                    'closed_by' => 'not_closed',
                    'closed_at' => now(),
                    'opened_at' => now(),
                ], $openedBy, $actorId));
            } elseif ($closure->opened_at !== null) {
                return $closure;
            } else {
                $closure->update($this->openingAuditAttributes([
                    'opened_at' => now(),
                ], $openedBy, $actorId));
            }

            ActivityLog::log(
                'cash_register_opened',
                'Caisse ouverte pour le vendeur '.$seller->name,
                'CashRegisterClosure',
                $closure->id,
                [
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'opened_by' => $openedBy,
                    'reason' => $reason,
                ]
            );

            return $closure;
        });
    }

    public function pendingClosureForSeller(User $seller, ?Carbon $date = null): ?CashRegisterClosure
    {
        $date ??= today();
        $closureQuery = CashRegisterClosure::where('seller_id', $seller->id)
            ->whereNull('opened_at');

        $closureQuery->whereDate('business_date', $date->toDateString());

        return $closureQuery
            ->latest('closed_at')
            ->first();
    }

    public function openRegisterForSeller(User $seller, ?Carbon $date = null): ?CashRegisterClosure
    {
        $date ??= today();

        return CashRegisterClosure::where('seller_id', $seller->id)
            ->whereDate('business_date', $date->toDateString())
            ->whereNotNull('opened_at')
            ->latest('opened_at')
            ->first();
    }

    public function isOpenForSeller(User $seller, ?Carbon $date = null): bool
    {
        return $this->openRegisterForSeller($seller, $date) !== null;
    }

    public function isClosedForSeller(User $seller, ?Carbon $date = null): bool
    {
        return ! $this->isOpenForSeller($seller, $date);
    }

    public function balanceForSeller(User $seller): float
    {
        $closedRevenue = CashRegisterClosure::where('seller_id', $seller->id)
            ->whereNull('opened_at')
            ->get()
            ->sum(fn (CashRegisterClosure $closure) => $this->cashRevenueForClosure($closure));
        $manualAdditions = CashBalanceAdjustment::where('seller_id', $seller->id)
            ->where('type', 'add')
            ->sum('amount');
        $manualWithdrawals = CashBalanceAdjustment::where('seller_id', $seller->id)
            ->where('type', 'withdraw')
            ->sum('amount');

        return (float) $closedRevenue + (float) $manualAdditions - (float) $manualWithdrawals;
    }

    public function currentDayCashSalesQuery(User $seller)
    {
        $currentSessionOpenedAt = $this->currentSessionOpenedAt($seller);

        $query = $this->cashSalesQueryForDate($seller, today());

        if ($currentSessionOpenedAt) {
            $query->where('created_at', '>=', $currentSessionOpenedAt);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function currentDaySalesQuery(User $seller)
    {
        $currentSessionOpenedAt = $this->currentSessionOpenedAt($seller);

        $query = $this->salesQueryForDate($seller, today());

        if ($currentSessionOpenedAt) {
            $query->where('created_at', '>=', $currentSessionOpenedAt);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function currentDayCashRevenueForSeller(User $seller): float
    {
        return (float) $this->currentDayCashSalesQuery($seller)->sum('total');
    }

    public function currentDayRevenueForSeller(User $seller): float
    {
        return (float) $this->currentDaySalesQuery($seller)->sum('total');
    }

    public function previousDayCashRevenueForSeller(User $seller): float
    {
        $businessDate = today()->subDay();
        $closure = CashRegisterClosure::where('seller_id', $seller->id)
            ->whereDate('business_date', $businessDate)
            ->first();

        return (float) ($closure?->amount ?? $this->cashRevenueForDate($seller, $businessDate));
    }

    public function currentSessionOpenedAt(User $seller, ?CashRegisterClosure $todayClosure = null): ?Carbon
    {
        $todayClosure ??= CashRegisterClosure::where('seller_id', $seller->id)
            ->whereDate('business_date', today())
            ->first();

        return $todayClosure?->opened_at;
    }

    public function cashRevenueForDate(User $seller, Carbon|string $date, ?Carbon $from = null): float
    {
        return (float) $this->cashSalesQueryForDate($seller, $date, $from)->sum('total');
    }

    public function cashRevenueForClosure(CashRegisterClosure $closure): float
    {
        return (float) $this->cashSalesQueryForSellerId($closure->seller_id, $closure->business_date)
            ->where('created_at', '<=', $closure->closed_at)
            ->sum('total');
    }

    public function cashSalesCountForDate(User $seller, Carbon|string $date): int
    {
        return $this->cashSalesQueryForDate($seller, $date)->count();
    }

    public function paymentBalanceForSeller(User $seller, string $paymentMethod): float
    {
        return CashRegisterClosure::where('seller_id', $seller->id)
            ->whereNull('opened_at')
            ->get()
            ->sum(fn (CashRegisterClosure $closure) => $this->paymentRevenueForClosure($closure, $paymentMethod));
    }

    public function orangeMoneyBalanceForSeller(User $seller): float
    {
        return $this->paymentBalanceForSeller($seller, self::ORANGE_MONEY_PAYMENT_METHOD);
    }

    public function mtnMomoBalanceForSeller(User $seller): float
    {
        return $this->paymentBalanceForSeller($seller, self::MTN_MOMO_PAYMENT_METHOD);
    }

    public function mobileMoneyBalanceForSeller(User $seller): float
    {
        return CashRegisterClosure::where('seller_id', $seller->id)
            ->whereNull('opened_at')
            ->get()
            ->sum(fn (CashRegisterClosure $closure) => $this->paymentRevenueForClosure($closure, [
                self::ORANGE_MONEY_PAYMENT_METHOD,
                self::MTN_MOMO_PAYMENT_METHOD,
            ]));
    }

    private function salesQueryForDate(User $seller, Carbon|string $date, ?Carbon $from = null)
    {
        $businessDate = $date instanceof Carbon ? $date->toDateString() : $date;
        $query = Sale::where('seller_id', $seller->id)
            ->whereDate('created_at', $businessDate);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        return $query;
    }

    private function cashSalesQueryForDate(User $seller, Carbon|string $date, ?Carbon $from = null)
    {
        return $this->cashSalesQueryForSellerId($seller->id, $date, $from);
    }

    private function cashSalesQueryForSellerId(int $sellerId, Carbon|string $date, ?Carbon $from = null)
    {
        $businessDate = $date instanceof Carbon ? $date->toDateString() : $date;
        $query = Sale::where('seller_id', $sellerId)
            ->where('payment_method', self::CASH_PAYMENT_METHOD)
            ->whereDate('created_at', $businessDate);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        return $query;
    }

    private function paymentRevenueForClosure(CashRegisterClosure $closure, string|array $paymentMethod): float
    {
        $query = Sale::where('seller_id', $closure->seller_id)
            ->whereDate('created_at', $closure->business_date)
            ->where('created_at', '<=', $closure->closed_at);

        if (is_array($paymentMethod)) {
            $query->whereIn('payment_method', $paymentMethod);
        } else {
            $query->where('payment_method', $paymentMethod);
        }

        return (float) $query->sum('total');
    }

    public function adjustBalance(User $seller, User $manager, string $type, float $amount, ?string $reason = null): CashBalanceAdjustment
    {
        return DB::transaction(function () use ($seller, $manager, $type, $amount, $reason) {
            if ($type === 'withdraw' && $this->balanceForSeller($seller) < $amount) {
                throw new \InvalidArgumentException('Le montant a retirer depasse le Solde Cash disponible.');
            }

            $adjustment = CashBalanceAdjustment::create([
                'seller_id' => $seller->id,
                'manager_id' => $manager->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            ActivityLog::log(
                $type === 'add' ? 'cash_balance_added' : 'cash_balance_withdrawn',
                ($type === 'add' ? 'Ajout' : 'Retrait').' Solde Cash vendeur '.$seller->name.' : '.number_format($amount, 0, ',', ' ').' FCFA',
                'CashBalanceAdjustment',
                $adjustment->id,
                [
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'manager_id' => $manager->id,
                    'manager_name' => $manager->name,
                    'amount' => $amount,
                    'reason' => $reason,
                    'alert_superadmin' => $type === 'withdraw',
                ]
            );

            return $adjustment;
        });
    }

    public function closeTodayForAllSellers(): int
    {
        $businessDate = today();
        $closedCount = 0;

        User::role('seller')->where('is_active', true)->chunkById(100, function ($sellers) use ($businessDate, &$closedCount) {
            foreach ($sellers as $seller) {
                if (! $this->isOpenForSeller($seller, $businessDate)) {
                    continue;
                }

                $closure = $this->closeForSeller($seller, $businessDate, 'automatic');

                $closedCount++;
            }
        });

        return $closedCount;
    }

    private function closureAuditAttributes(array $attributes, ?int $actorId, bool $clearOpening = false): array
    {
        if (Schema::hasColumn('cash_register_closures', 'closed_by_user_id')) {
            $attributes['closed_by_user_id'] = $actorId;
        }

        if ($clearOpening) {
            if (Schema::hasColumn('cash_register_closures', 'opened_by')) {
                $attributes['opened_by'] = null;
            }

            if (Schema::hasColumn('cash_register_closures', 'opened_by_user_id')) {
                $attributes['opened_by_user_id'] = null;
            }
        }

        return $attributes;
    }

    private function openingAuditAttributes(array $attributes, string $openedBy, ?int $actorId): array
    {
        if (Schema::hasColumn('cash_register_closures', 'opened_by')) {
            $attributes['opened_by'] = $openedBy;
        }

        if (Schema::hasColumn('cash_register_closures', 'opened_by_user_id')) {
            $attributes['opened_by_user_id'] = $actorId;
        }

        return $attributes;
    }
}
