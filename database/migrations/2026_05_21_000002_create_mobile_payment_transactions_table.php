<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('provider')->default('monetbil');
            $table->string('payment_ref')->unique();
            $table->string('payment_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->string('operator')->nullable();
            $table->string('phone', 20);
            $table->decimal('amount', 12, 2);
            $table->string('customer_name')->nullable();
            $table->json('cart_payload');
            $table->json('provider_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_payment_transactions');
    }
};
