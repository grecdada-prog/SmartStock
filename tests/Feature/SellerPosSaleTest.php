<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CashRegisterClosure;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerPosSaleTest extends TestCase
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

    public function test_seller_sale_uses_server_price_and_records_stock_output(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Eau minerale',
            'sku' => 'EAU-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $response = $this->actingAs($seller)->postJson(route('seller.pos.sale'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => 1,
                ],
            ],
            'payment_method' => 'cash',
            'amount_received' => 500,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', '200.00')
            ->assertJsonPath('change', '300.00');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'subtotal' => 200,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 2,
            'quantity_before' => 5,
            'quantity_after' => 3,
        ]);
    }

    public function test_seller_cannot_sell_product_from_another_manager(): void
    {
        $seller = $this->createSellerWithManager();
        $otherManager = User::factory()->create();
        $otherManager->assignRole('manager');

        $category = Category::create([
            'name' => 'Autre stock',
            'created_by' => $otherManager->id,
        ]);
        $product = Product::create([
            'name' => 'Produit autre manager',
            'sku' => 'AUTRE-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $otherManager->id,
        ]);

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

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, StockMovement::count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_seller_cannot_sell_when_cash_register_is_closed(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Eau minerale',
            'sku' => 'EAU-CLOSED-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        CashRegisterClosure::create([
            'seller_id' => $seller->id,
            'business_date' => today(),
            'amount' => 0,
            'closed_by' => 'manual',
            'closed_at' => now(),
        ]);

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

        $response->assertStatus(423)
            ->assertJsonPath('success', false);

        $this->assertSame(0, Sale::count());
    }

    public function test_mobile_money_payments_require_customer_phone(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Jus',
            'sku' => 'JUS-MOMO-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $response = $this->actingAs($seller)->postJson(route('seller.pos.sale'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'mobile_money',
            'amount_received' => 100,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('customer_phone');

        $this->assertSame(0, Sale::count());
    }

    public function test_seller_can_refresh_available_products_for_pos(): void
    {
        $seller = $this->createSellerWithManager();
        $otherManager = User::factory()->create();
        $otherManager->assignRole('manager');

        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);

        $visibleProduct = Product::create([
            'name' => 'Jus orange',
            'sku' => 'JUS-001',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 4,
            'alert_quantity' => 1,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        Product::create([
            'name' => 'Produit vide',
            'sku' => 'VIDE-001',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 0,
            'alert_quantity' => 1,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        Product::create([
            'name' => 'Autre manager',
            'sku' => 'AUTRE-MANAGER-001',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 4,
            'alert_quantity' => 1,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $otherManager->id,
        ]);

        $response = $this->actingAs($seller)->getJson(route('seller.pos.products'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.id', $visibleProduct->id)
            ->assertJsonPath('products.0.quantity', 4);
    }

    public function test_seller_sale_consumes_restock_batches_fifo(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Soda',
            'sku' => 'SODA-FIFO-001',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 5,
            'alert_quantity' => 1,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $firstBatch = StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 3,
            'quantity_before' => 0,
            'quantity_after' => 3,
            'purchase_price' => 100,
            'selling_price' => 200,
            'remaining_quantity' => 3,
            'batch_code' => 'LOT-OLD',
            'user_id' => $seller->created_by,
        ]);
        $secondBatch = StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 2,
            'quantity_before' => 3,
            'quantity_after' => 5,
            'purchase_price' => 120,
            'selling_price' => 200,
            'remaining_quantity' => 2,
            'batch_code' => 'LOT-NEW',
            'user_id' => $seller->created_by,
        ]);

        $response = $this->actingAs($seller)->postJson(route('seller.pos.sale'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
            'payment_method' => 'cash',
            'amount_received' => 1000,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $firstBatch->id,
            'remaining_quantity' => 0,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'id' => $secondBatch->id,
            'remaining_quantity' => 1,
        ]);
    }

    private function createSellerWithManager(): User
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $seller = User::factory()->create([
            'created_by' => $manager->id,
        ]);
        $seller->assignRole('seller');

        return $seller;
    }
}
