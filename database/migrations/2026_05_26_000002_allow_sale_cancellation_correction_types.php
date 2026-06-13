<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE stock_movements MODIFY type ENUM('in', 'out', 'adjustment', 'correction_cancellation') NOT NULL");
        DB::statement("ALTER TABLE cash_balance_adjustments MODIFY type ENUM('add', 'withdraw', 'correction_cancellation') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE stock_movements MODIFY type ENUM('in', 'out', 'adjustment') NOT NULL");
        DB::statement("ALTER TABLE cash_balance_adjustments MODIFY type ENUM('add', 'withdraw') NOT NULL");
    }
};
