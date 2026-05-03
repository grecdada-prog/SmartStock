<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('amount_received', 10, 2)->nullable()->after('total');
            $table->decimal('change_given', 10, 2)->default(0)->after('amount_received');
            $table->string('customer_name')->nullable()->after('change_given');
            $table->string('customer_phone')->nullable()->after('customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['amount_received', 'change_given', 'customer_name', 'customer_phone']);
        });
    }
};
