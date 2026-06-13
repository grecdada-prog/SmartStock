<?php

namespace App\Console\Commands;

use App\Services\CashRegisterService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CloseSellerCashRegisters extends Command
{
    private const BUSINESS_TIMEZONE = 'Africa/Douala';

    protected $signature = 'cash-registers:close-daily {--date= : Date de caisse a fermer (YYYY-MM-DD)} {--yesterday : Fermer la journee de caisse precedente}';

    protected $description = 'Close seller cash registers for a business date';

    public function handle(CashRegisterService $cashRegisterService): int
    {
        try {
            $businessDate = $this->businessDate();
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $count = $cashRegisterService->closeForAllSellersForDate($businessDate);

        $this->info("{$count} caisse(s) vendeur fermee(s) pour le {$businessDate->format('Y-m-d')}.");

        return self::SUCCESS;
    }

    private function businessDate(): Carbon
    {
        if ($this->option('date')) {
            try {
                return Carbon::createFromFormat('Y-m-d', (string) $this->option('date'))->startOfDay();
            } catch (\Throwable) {
                throw new \InvalidArgumentException('La date doit etre au format YYYY-MM-DD.');
            }
        }

        if ($this->option('yesterday')) {
            return Carbon::now(self::BUSINESS_TIMEZONE)->subDay()->startOfDay();
        }

        return Carbon::now(self::BUSINESS_TIMEZONE)->startOfDay();
    }
}
