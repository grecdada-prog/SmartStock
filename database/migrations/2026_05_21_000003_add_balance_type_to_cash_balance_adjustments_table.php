<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_balance_adjustments', function (Blueprint $table) {
            $table->string('balance_type')->default('cash')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('cash_balance_adjustments', function (Blueprint $table) {
            $table->dropColumn('balance_type');
        });
    }
};
