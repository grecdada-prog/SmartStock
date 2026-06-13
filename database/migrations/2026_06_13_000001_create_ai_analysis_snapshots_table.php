<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('generated_at');
            $table->unsignedSmallInteger('period_days');
            $table->unsignedSmallInteger('target_days');
            $table->json('kpis');
            $table->json('stock_predictions');
            $table->json('expiry_alerts');
            $table->json('anomalies');
            $table->text('narrative');
            $table->json('methodology');
            $table->timestamps();

            $table->index(['manager_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_snapshots');
    }
};
