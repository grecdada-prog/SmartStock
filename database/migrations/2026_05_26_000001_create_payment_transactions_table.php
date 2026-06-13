<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 80)->unique();
            $table->string('type', 40);
            $table->string('status', 40)->default('pending');
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('payment_method', 40)->nullable();
            $table->string('operator_code', 80)->nullable();
            $table->string('operator_label', 80)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('operator_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('XAF');
            $table->string('country', 10)->default('CM');
            $table->string('monetbil_payment_id', 120)->nullable()->index();
            $table->string('monetbil_transaction_uuid', 120)->nullable()->index();
            $table->string('monetbil_status', 80)->nullable();
            $table->string('monetbil_message')->nullable();
            $table->json('sale_payload')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->json('last_check_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['manager_id', 'status']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
