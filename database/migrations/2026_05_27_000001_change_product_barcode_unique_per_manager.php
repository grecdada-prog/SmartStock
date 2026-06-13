<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->unique(['created_by', 'barcode'], 'products_created_by_barcode_unique');
        });
    }

    public function down(): void
    {
        $hasDuplicateBarcodes = DB::table('products')
            ->select('barcode')
            ->whereNotNull('barcode')
            ->groupBy('barcode')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_created_by_barcode_unique');

            if (! $hasDuplicateBarcodes) {
                $table->unique('barcode');
            }
        });
    }
};
