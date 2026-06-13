<?php

namespace Tests\Feature;

use App\Models\CashBalanceAdjustment;
use App\Models\Category;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagerSaleDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\PreventDirectAccess::class,
            \App\Http\Middleware\SingleSessionMiddleware::class,
            \App\Http\Middleware\CheckInactivity::class,
            \App\Http\Middleware\CheckUserActive::class,
        ]);
    }

    public function test_manager_can_delete_own_seller_sale_and_corrections_are_recorded(): void
    {
        [$manager, $seller, $product, $sale] = $this->createProductSale('cash');

        PaymentTransaction::create([
            'reference' => 'PAY-CANCEL-001',
            'type' => PaymentTransaction::TYPE_POS_SALE,
            'status' => PaymentTransaction::STATUS_SUCCESS,
            'seller_id' => $seller->id,
            'manager_id' => $manager->id,
            'sale_id' => $sale->id,
            'amount' => 200,
            'total_amount' => 200,
        ]);

        $this->assertSame(200.0, app(CashRegisterService::class)->balanceForSeller($seller));

        $this->actingAs($manager)
            ->delete(route('manager.sales.destroy', $sale))
            ->assertRedirect()
            ->assertSessionHas('success', 'Vente supprimee. Stock et soldes corriges.');

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_CORRECTION_CANCELLATION,
            'quantity' => 2,
            'quantity_before' => 3,
            'quantity_after' => 5,
            'remaining_quantity' => 2,
            'reference' => 'Annulation vente #'.$sale->invoice_number,
        ]);
        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'manager_id' => $manager->id,
            'type' => 'correction_cancellation',
            'balance_type' => CashRegisterService::CASH_BALANCE_TYPE,
            'amount' => 200,
        ]);
        $this->assertDatabaseHas('payment_transactions', [
            'reference' => 'PAY-CANCEL-001',
            'sale_id' => null,
        ]);
        $this->assertSame(0.0, app(CashRegisterService::class)->balanceForSeller($seller));
    }

    public function test_manager_cannot_delete_another_manager_seller_sale(): void
    {
        [, , , $sale] = $this->createProductSale('cash');
        $otherManager = $this->createUserWithRole('manager');

        $this->actingAs($otherManager)
            ->delete(route('manager.sales.destroy', $sale))
            ->assertRedirect()
            ->assertSessionHas('error', 'Vente hors perimetre du gerant.');

        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    public function test_mobile_money_sale_deletion_records_mobile_money_correction_without_double_subtracting(): void
    {
        [$manager, $seller, , $sale] = $this->createProductSale('mobile_money', 204);

        $this->assertSame(204.0, app(CashRegisterService::class)->mobileMoneyBalanceForSeller($seller));

        $this->actingAs($manager)
            ->delete(route('manager.sales.destroy', $sale))
            ->assertRedirect();

        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'manager_id' => $manager->id,
            'type' => 'correction_cancellation',
            'balance_type' => CashRegisterService::MOBILE_MONEY_BALANCE_TYPE,
            'amount' => 204,
        ]);
        $this->assertSame(0.0, app(CashRegisterService::class)->mobileMoneyBalanceForSeller($seller));
    }

    public function test_restored_correction_stock_can_be_sold_again(): void
    {
        [$manager, $seller, $product, $sale] = $this->createProductSale('cash');

        $this->actingAs($manager)->delete(route('manager.sales.destroy', $sale));
        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));

        $response = $this->actingAs($seller)->postJson(route('seller.pos.sale'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'cash',
            'amount_received' => 100,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_CORRECTION_CANCELLATION,
            'remaining_quantity' => 1,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 4,
        ]);
    }

    private function createProductSale(string $paymentMethod, float $total = 200): array
    {
        $manager = $this->createUserWithRole('manager');
        $seller = $this->createUserWithRole('seller', ['created_by' => $manager->id]);
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $manager->id,
        ]);
        $product = Product::create([
            'name' => 'Eau test',
            'sku' => 'EAU-CANCEL',
            'barcode' => '1234567890123',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 3,
            'alert_quantity' => 2,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);
        $sale = Sale::create([
            'seller_id' => $seller->id,
            'invoice_number' => 'INV-CANCEL-'.strtoupper(fake()->bothify('??##')),
            'subtotal' => $total,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'amount_received' => $total,
            'change_given' => 0,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $total / 2,
            'subtotal' => $total,
        ]);

        return [$manager, $seller, $product, $sale];
    }

    private function createUserWithRole(string $role, array $attributes = []): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
