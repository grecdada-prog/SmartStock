<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CashRegisterClosure;
use App\Models\MobilePaymentTransaction;
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
        $seller = $this->createSellerWithManager(openRegister: false);
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

    public function test_mobile_payment_start_keeps_sale_pending_until_monetbil_callback(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Jus',
            'sku' => 'JUS-MONETBIL-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $response = $this->actingAs($seller)->postJson(route('seller.pos.mobile-payment.start'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'mobile_money',
            'customer_phone' => '690100300',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('success', true)
            ->assertJsonPath('operator_label', 'Orange Money')
            ->assertJsonPath('awaiting_callback', true);

        $this->assertSame(0, Sale::count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 5,
        ]);
        $this->assertSame(MobilePaymentTransaction::STATUS_PENDING, MobilePaymentTransaction::firstOrFail()->status);
    }

    public function test_monetbil_success_callback_records_mobile_sale(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Jus',
            'sku' => 'JUS-CALLBACK-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $transaction = MobilePaymentTransaction::create([
            'seller_id' => $seller->id,
            'payment_ref' => 'MB-CALLBACK-OK',
            'status' => MobilePaymentTransaction::STATUS_PENDING,
            'operator' => 'CM_ORANGEMONEY',
            'phone' => '690100300',
            'amount' => 200,
            'cart_payload' => [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ],
        ]);

        $this->postJson(route('payments.monetbil.callback'), [
            'service' => config('services.monetbil.service_key'),
            'payment_ref' => $transaction->payment_ref,
            'amount' => 200,
            'status' => 'success',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, Sale::count());
        $this->assertDatabaseHas('sales', [
            'seller_id' => $seller->id,
            'payment_method' => 'card',
            'customer_phone' => '690100300',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertSame(MobilePaymentTransaction::STATUS_SUCCESS, $transaction->fresh()->status);
    }

    public function test_monetbil_insufficient_funds_callback_does_not_record_sale(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Jus',
            'sku' => 'JUS-CALLBACK-FAILED-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $transaction = MobilePaymentTransaction::create([
            'seller_id' => $seller->id,
            'payment_ref' => 'MB-CALLBACK-KO',
            'status' => MobilePaymentTransaction::STATUS_PENDING,
            'operator' => 'CM_MTNMOBILEMONEY',
            'phone' => '670100300',
            'amount' => 200,
            'cart_payload' => [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ],
        ]);

        $this->postJson(route('payments.monetbil.callback'), [
            'service' => config('services.monetbil.service_key'),
            'payment_ref' => $transaction->payment_ref,
            'amount' => 200,
            'status' => 'failed',
            'message' => 'insufficient funds',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, Sale::count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 5,
        ]);
        $this->assertSame(MobilePaymentTransaction::STATUS_FAILED, $transaction->fresh()->status);
        $this->assertSame('Fonds insuffisants sur le compte mobile.', $transaction->fresh()->failure_reason);
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

    public function test_pos_product_list_displays_oldest_available_batch_price(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boulangerie',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Pain',
            'sku' => 'PAIN-FIFO-001',
            'category_id' => $category->id,
            'purchase_price' => 150,
            'selling_price' => 200,
            'quantity' => 4,
            'alert_quantity' => 1,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 2,
            'quantity_before' => 0,
            'quantity_after' => 2,
            'purchase_price' => 120,
            'selling_price' => 150,
            'remaining_quantity' => 2,
            'batch_code' => 'LOT-OLD',
            'user_id' => $seller->created_by,
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 2,
            'quantity_before' => 2,
            'quantity_after' => 4,
            'purchase_price' => 150,
            'selling_price' => 200,
            'remaining_quantity' => 2,
            'batch_code' => 'LOT-NEW',
            'user_id' => $seller->created_by,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($seller)->getJson(route('seller.pos.products'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.selling_price', '200.00')
            ->assertJsonPath('products.0.fifo_selling_price', 150)
            ->assertJsonCount(2, 'products.0.stock_movements')
            ->assertJsonPath('products.0.stock_movements.0.selling_price', '150.00')
            ->assertJsonPath('products.0.stock_movements.0.remaining_quantity', 2)
            ->assertJsonPath('products.0.stock_movements.1.selling_price', '200.00')
            ->assertJsonPath('products.0.stock_movements.1.remaining_quantity', 2);
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

    public function test_seller_sale_uses_oldest_batch_selling_price_first(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Jus naturel',
            'sku' => 'JUS-FIFO-PRICE-001',
            'category_id' => $category->id,
            'purchase_price' => 120,
            'selling_price' => 200,
            'quantity' => 5,
            'alert_quantity' => 1,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 3,
            'quantity_before' => 0,
            'quantity_after' => 3,
            'purchase_price' => 90,
            'selling_price' => 150,
            'remaining_quantity' => 3,
            'batch_code' => 'LOT-HIER',
            'user_id' => $seller->created_by,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 2,
            'quantity_before' => 3,
            'quantity_after' => 5,
            'purchase_price' => 120,
            'selling_price' => 200,
            'remaining_quantity' => 2,
            'batch_code' => 'LOT-AUJOURDHUI',
            'user_id' => $seller->created_by,
            'created_at' => now(),
            'updated_at' => now(),
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

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', '650.00')
            ->assertJsonPath('change', '350.00');

        $sale = Sale::firstOrFail();

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 150,
            'subtotal' => 450,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 200,
            'subtotal' => 200,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
            'purchase_price' => 90,
            'selling_price' => 150,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 1,
            'purchase_price' => 120,
            'selling_price' => 200,
        ]);
    }

    private function createSellerWithManager(bool $openRegister = true): User
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $seller = User::factory()->create([
            'created_by' => $manager->id,
        ]);
        $seller->assignRole('seller');

        if ($openRegister) {
            $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        }

        return $seller;
    }
}
