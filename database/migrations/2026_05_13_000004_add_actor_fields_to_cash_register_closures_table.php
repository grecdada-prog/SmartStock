<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_register_closures', function (Blueprint $table) {
            $table->foreignId('closed_by_user_id')
                ->nullable()
                ->after('closed_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('opened_by')->nullable()->after('opened_at');
            $table->foreignId('opened_by_user_id')
                ->nullable()
                ->after('opened_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_register_closures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opened_by_user_id');
            $table->dropColumn('opened_by');
            $table->dropConstrainedForeignId('closed_by_user_id');
        });
    }
};
