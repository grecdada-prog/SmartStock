<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->nullable()->after('quantity_after');
            $table->decimal('selling_price', 10, 2)->nullable()->after('purchase_price');
            $table->integer('remaining_quantity')->nullable()->after('selling_price');
            $table->string('batch_code')->nullable()->after('remaining_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['purchase_price', 'selling_price', 'remaining_quantity', 'batch_code']);
        });
    }
};
