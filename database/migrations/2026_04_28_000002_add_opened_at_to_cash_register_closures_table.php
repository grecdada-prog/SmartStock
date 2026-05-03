<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_register_closures', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('cash_register_closures', function (Blueprint $table) {
            $table->dropColumn('opened_at');
        });
    }
};
