<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_balance_adjustments', function (Blueprint $table) {
            // 'manual'  = ajustement gérant depuis le back-office
            // 'service' = opération dépôt/retrait momo réalisée par la vendeuse
            // 'restock' = paiement livreur appro direct
            $table->string('source')->default('manual')->after('reason');

            // Regroupe les deux lignes d'une même opération dépôt/retrait (UUID)
            $table->string('reference_id')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('cash_balance_adjustments', function (Blueprint $table) {
            $table->dropColumn(['source', 'reference_id']);
        });
    }
};
