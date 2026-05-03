<?php

namespace App\Console\Commands;

use App\Services\CashRegisterService;
use Illuminate\Console\Command;

class CloseSellerCashRegisters extends Command
{
    protected $signature = 'cash-registers:close-daily';

    protected $description = 'Close seller cash registers for the current day';

    public function handle(CashRegisterService $cashRegisterService): int
    {
        $count = $cashRegisterService->closeTodayForAllSellers();

        $this->info("{$count} caisse(s) vendeur fermee(s).");

        return self::SUCCESS;
    }
}
