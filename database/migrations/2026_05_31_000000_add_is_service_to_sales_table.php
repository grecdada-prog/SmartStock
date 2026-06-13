<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_service')->default(false)->after('notes');
            $table->string('service_type')->nullable()->after('is_service'); // 'energy_token', 'cash_service'
            $table->index(['is_service', 'created_at']);
            $table->index(['seller_id', 'is_service']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['is_service', 'service_type']);
            $table->dropIndex(['is_service', 'created_at']);
            $table->dropIndex(['seller_id', 'is_service']);
        });
    }
};
