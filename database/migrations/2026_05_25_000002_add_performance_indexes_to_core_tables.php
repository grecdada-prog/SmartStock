<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['created_by', 'is_active', 'created_at'], 'idx_users_creator_active_created');
            $table->index(['is_active', 'created_at'], 'idx_users_active_created');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['created_by', 'is_active', 'name'], 'idx_categories_owner_active_name');
            $table->index(['created_by', 'created_at'], 'idx_categories_owner_created');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['created_by', 'is_active', 'deleted_at', 'name'], 'idx_products_owner_active_name');
            $table->index(['created_by', 'is_active', 'deleted_at', 'quantity'], 'idx_products_owner_active_qty');
            $table->index(['created_by', 'category_id', 'is_active'], 'idx_products_owner_category_active');
            $table->index(['created_by', 'created_at'], 'idx_products_owner_created');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['seller_id', 'created_at'], 'idx_sales_seller_created');
            $table->index(['seller_id', 'payment_method', 'created_at'], 'idx_sales_seller_payment_created');
            $table->index(['payment_method', 'created_at'], 'idx_sales_payment_created');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['product_id', 'sale_id'], 'idx_sale_items_product_sale');
            $table->index(['sale_id', 'product_id'], 'idx_sale_items_sale_product');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'type', 'created_at', 'id'], 'idx_stock_movements_product_type_created');
            $table->index(['product_id', 'type', 'remaining_quantity', 'created_at'], 'idx_stock_movements_product_type_remaining');
            $table->index(['user_id', 'created_at'], 'idx_stock_movements_user_created');
        });

        Schema::table('product_promotions', function (Blueprint $table) {
            $table->index(['manager_id', 'product_id', 'status', 'min_quantity'], 'idx_promos_manager_product_status_min');
            $table->index(['product_id', 'status', 'min_quantity'], 'idx_promos_product_status_min');
        });

        Schema::table('cash_register_closures', function (Blueprint $table) {
            $table->index(['seller_id', 'closed_at'], 'idx_cash_closures_seller_closed');
            $table->index(['closed_by', 'closed_at'], 'idx_cash_closures_closed_by_at');
        });

        Schema::table('cash_balance_adjustments', function (Blueprint $table) {
            $table->index(['seller_id', 'balance_type', 'created_at'], 'idx_cash_adjustments_seller_balance_created');
            $table->index(['manager_id', 'balance_type', 'created_at'], 'idx_cash_adjustments_manager_balance_created');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_activity_logs_user_created');
            $table->index(['action', 'created_at'], 'idx_activity_logs_action_created');
            $table->index(['model', 'model_id'], 'idx_activity_logs_model');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_logs_model');
            $table->dropIndex('idx_activity_logs_action_created');
            $table->dropIndex('idx_activity_logs_user_created');
        });

        Schema::table('cash_balance_adjustments', function (Blueprint $table) {
            $table->dropIndex('idx_cash_adjustments_manager_balance_created');
            $table->dropIndex('idx_cash_adjustments_seller_balance_created');
        });

        Schema::table('cash_register_closures', function (Blueprint $table) {
            $table->dropIndex('idx_cash_closures_closed_by_at');
            $table->dropIndex('idx_cash_closures_seller_closed');
        });

        Schema::table('product_promotions', function (Blueprint $table) {
            $table->dropIndex('idx_promos_product_status_min');
            $table->dropIndex('idx_promos_manager_product_status_min');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_user_created');
            $table->dropIndex('idx_stock_movements_product_type_remaining');
            $table->dropIndex('idx_stock_movements_product_type_created');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_sale_product');
            $table->dropIndex('idx_sale_items_product_sale');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_payment_created');
            $table->dropIndex('idx_sales_seller_payment_created');
            $table->dropIndex('idx_sales_seller_created');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_owner_created');
            $table->dropIndex('idx_products_owner_category_active');
            $table->dropIndex('idx_products_owner_active_qty');
            $table->dropIndex('idx_products_owner_active_name');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_owner_created');
            $table->dropIndex('idx_categories_owner_active_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_active_created');
            $table->dropIndex('idx_users_creator_active_created');
        });
    }
};
