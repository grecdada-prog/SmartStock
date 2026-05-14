<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashBalanceAdjustment;
use App\Models\CashRegisterClosure;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    public function closeForSeller(User $seller, ?Carbon $date = null, string $closedBy = 'manual', ?string $reason = null): CashRegisterClosure
    {
        $businessDate = ($date ?? today())->toDateString();

        return DB::transaction(function () use ($seller, $businessDate, $closedBy, $reason) {
            $actorId = auth()->id();

            $pendingClosure = CashRegisterClosure::where('seller_id', $seller->id)
                ->whereNull('opened_at')
                ->latest('closed_at')
                ->lockForUpdate()
                ->first();

            if ($pendingClosure) {
                return $pendingClosure;
            }

            $existingClosure = CashRegisterClosure::where('seller_id', $seller->id)
                ->whereDate('business_date', $businessDate)
                ->lockForUpdate()
                ->first();

            if ($existingClosure) {
                if ($existingClosure->opened_at === null) {
                    return $existingClosure;
                }

                $amount = Sale::where('seller_id', $seller->id)
                    ->whereDate('created_at', $businessDate)
                    ->where('created_at', '>=', $existingClosure->opened_at)
                    ->sum('total');

                $existingClosure->update([
                    'amount' => (float) $existingClosure->amount + (float) $amount,
                    'closed_by' => $closedBy,
                    'closed_by_user_id' => $actorId,
                    'closed_at' => now(),
                    'opened_at' => null,
                    'opened_by' => null,
                    'opened_by_user_id' => null,
                ]);

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

            $amount = Sale::where('seller_id', $seller->id)
                ->whereDate('created_at', $businessDate)
                ->sum('total');

            $closure = CashRegisterClosure::create([
                'seller_id' => $seller->id,
                'business_date' => $businessDate,
                'amount' => $amount,
                'closed_by' => $closedBy,
                'closed_by_user_id' => $actorId,
                'closed_at' => now(),
            ]);

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

            $closureQuery = CashRegisterClosure::where('seller_id', $seller->id)
                ->whereNull('opened_at');

            if ($date) {
                $closureQuery->whereDate('business_date', $date->toDateString());
            }

            $closure = $closureQuery
                ->latest('closed_at')
                ->lockForUpdate()
                ->first();

            if (!$closure) {
                return $closure;
            }

            $closure->update([
                'opened_at' => now(),
                'opened_by' => $openedBy,
                'opened_by_user_id' => $actorId,
            ]);

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

    public function isClosedForSeller(User $seller, ?Carbon $date = null): bool
    {
        $closureQuery = CashRegisterClosure::where('seller_id', $seller->id)
            ->whereNull('opened_at');

        if ($date) {
            $closureQuery->whereDate('business_date', $date->toDateString());
        }

        return $closureQuery->exists();
    }

    public function balanceForSeller(User $seller): float
    {
        $closedRevenue = CashRegisterClosure::where('seller_id', $seller->id)->sum('amount');
        $manualAdditions = CashBalanceAdjustment::where('seller_id', $seller->id)
            ->where('type', 'add')
            ->sum('amount');
        $manualWithdrawals = CashBalanceAdjustment::where('seller_id', $seller->id)
            ->where('type', 'withdraw')
            ->sum('amount');

        return (float) $closedRevenue + (float) $manualAdditions - (float) $manualWithdrawals;
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
                $closure = $this->closeForSeller($seller, $businessDate, 'automatic');

                if ($closure->wasRecentlyCreated) {
                    $closedCount++;
                }
            }
        });

        return $closedCount;
    }
}
