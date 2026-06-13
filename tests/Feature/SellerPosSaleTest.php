<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CashRegisterClosure;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AvlyTextSmsService;
use App\Services\CashRegisterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
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

    public function test_seller_sale_with_same_client_token_is_not_recorded_twice(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Eau token',
            'sku' => 'EAU-TOKEN-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $payload = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'cash',
            'amount_received' => 500,
            'client_sale_token' => 'seller-token-001',
        ];

        $firstResponse = $this->actingAs($seller)->postJson(route('seller.pos.sale'), $payload);
        $secondResponse = $this->actingAs($seller)->postJson(route('seller.pos.sale'), $payload);

        $firstResponse->assertOk()->assertJsonPath('success', true);
        $secondResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sale_id', $firstResponse->json('sale_id'));

        $this->assertSame(1, Sale::count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertSame(1, StockMovement::where('type', 'out')->count());
    }

    public function test_seller_history_can_filter_sales_by_product_name_or_barcode(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Epicerie',
            'created_by' => $seller->created_by,
        ]);
        $targetProduct = Product::create([
            'name' => 'Biscuit cible',
            'sku' => 'BISC-CIBLE-001',
            'barcode' => '604300002506',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);
        $otherProduct = Product::create([
            'name' => 'Savon autre',
            'sku' => 'SAVON-AUTRE-001',
            'barcode' => '604300002507',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $targetSale = $this->actingAs($seller)->postJson(route('seller.pos.sale'), [
            'items' => [['product_id' => $targetProduct->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_received' => 100,
            'client_sale_token' => 'history-target-token',
        ])->json('invoice_number');
        $otherSale = $this->actingAs($seller)->postJson(route('seller.pos.sale'), [
            'items' => [['product_id' => $otherProduct->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_received' => 100,
            'client_sale_token' => 'history-other-token',
        ])->json('invoice_number');

        $this->actingAs($seller)
            ->get(route('seller.sales.history', ['product_search' => 'Biscuit']))
            ->assertOk()
            ->assertSee($targetSale)
            ->assertDontSee($otherSale);

        $this->actingAs($seller)
            ->get(route('seller.sales.history', ['product_search' => '604300002506']))
            ->assertOk()
            ->assertSee($targetSale)
            ->assertDontSee($otherSale);
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

    public function test_pos_mobile_payment_records_sale_directly_without_monetbil_transaction(): void
    {
        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Jus direct mobile',
            'sku' => 'JUS-DIRECT-MOBILE-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $this->actingAs($seller)->postJson(route('seller.pos.sale'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => 'mobile_money',
            'amount_received' => 204,
            'customer_phone' => '690100300',
            'client_sale_token' => 'mobile-direct-001',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, Sale::count());
        $this->assertSame(0, PaymentTransaction::count());
        $this->assertDatabaseHas('sales', [
            'seller_id' => $seller->id,
            'payment_method' => 'card',
            'subtotal' => 200,
            'total' => 204,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_mobile_payment_waits_for_monetbil_before_recording_sale(): void
    {
        config(['services.monetbil.fake_mode' => true]);
        config(['services.monetbil.fake_result' => 'success']);

        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Jus',
            'sku' => 'JUS-MOBILE-001',
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
            'client_sale_token' => 'mobile-monetbil-001',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', PaymentTransaction::STATUS_PENDING);

        $this->assertSame(0, Sale::count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 5,
        ]);

        $transaction = PaymentTransaction::firstOrFail();

        $checkResponse = $this->actingAs($seller)->postJson(route('seller.payments.check', $transaction));
        $secondCheckResponse = $this->actingAs($seller)->postJson(route('seller.payments.check', $transaction));

        $checkResponse->assertOk()
            ->assertJsonPath('status', PaymentTransaction::STATUS_SUCCESS)
            ->assertJsonPath('success', true);
        $secondCheckResponse->assertOk()
            ->assertJsonPath('status', PaymentTransaction::STATUS_SUCCESS);

        $this->assertSame(1, Sale::count());
        $this->assertDatabaseHas('sales', [
            'seller_id' => $seller->id,
            'payment_method' => 'card',
            'customer_phone' => '690100300',
            'subtotal' => 200,
            'total' => 204,
            'amount_received' => 204,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_expired_mobile_payment_is_checked_before_being_marked_expired(): void
    {
        config(['services.monetbil.fake_mode' => true]);
        config(['services.monetbil.fake_result' => 'success']);

        $seller = $this->createSellerWithManager();
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $seller->created_by,
        ]);
        $product = Product::create([
            'name' => 'Soda',
            'sku' => 'SODA-MOBILE-001',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 5,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $this->actingAs($seller)->postJson(route('seller.pos.mobile-payment.start'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'mobile_money',
            'customer_phone' => '690100300',
            'client_sale_token' => 'expired-paid-token',
        ])->assertOk();

        $transaction = PaymentTransaction::firstOrFail();
        $transaction->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($seller)
            ->postJson(route('seller.payments.check', $transaction))
            ->assertOk()
            ->assertJsonPath('status', PaymentTransaction::STATUS_SUCCESS);

        $this->assertSame(1, Sale::count());
    }

    public function test_monetbil_check_payment_is_sent_as_form_data(): void
    {
        config(['services.monetbil.fake_mode' => false]);
        config(['services.monetbil.verify_ssl' => false]);

        Http::fake([
            'api.monetbil.com/payment/v1/checkPayment' => Http::response([
                'paymentId' => 'PAYMENT-123',
                'message' => 'payment pending',
            ]),
        ]);

        $seller = $this->createSellerWithManager();
        $transaction = PaymentTransaction::create([
            'reference' => 'PAY-FORM-CHECK',
            'type' => PaymentTransaction::TYPE_POS_SALE,
            'status' => PaymentTransaction::STATUS_PENDING,
            'seller_id' => $seller->id,
            'manager_id' => $seller->created_by,
            'customer_phone' => '690100300',
            'amount' => 100,
            'operator_fee' => 2,
            'total_amount' => 102,
            'monetbil_payment_id' => 'PAYMENT-123',
            'expires_at' => now()->addMinutes(3),
        ]);

        app(\App\Services\MonetbilPaymentService::class)->checkPayment($transaction);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.monetbil.com/payment/v1/checkPayment'
                && str_contains($request->header('Content-Type')[0] ?? '', 'application/x-www-form-urlencoded')
                && $request['paymentId'] === 'PAYMENT-123';
        });
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
            'barcode' => '6 9455 85 0039 13',
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
            ->assertJsonPath('products.0.barcode', '6 9455 85 0039 13')
            ->assertJsonPath('products.0.quantity', 4);
    }

    public function test_seller_product_search_uses_barcode_not_sku(): void
    {
        $seller = $this->createSellerWithManager();

        $category = Category::create([
            'name' => 'Epicerie',
            'created_by' => $seller->created_by,
        ]);

        Product::create([
            'name' => 'Seller Barcode Target',
            'sku' => 'SELLER-BARCODE-SKU',
            'barcode' => '6 9455 85 0039 13',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 5,
            'alert_quantity' => 1,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        Product::create([
            'name' => 'Seller Sku Only Target',
            'sku' => 'SELLER-SKU-ONLY',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 5,
            'alert_quantity' => 1,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $seller->created_by,
        ]);

        $this->actingAs($seller)
            ->get(route('seller.products', ['search' => '6945585003913']))
            ->assertOk()
            ->assertSee('Seller Barcode Target')
            ->assertDontSee('Seller Sku Only Target');

        $this->actingAs($seller)
            ->get(route('seller.products', ['search' => 'SELLER-SKU-ONLY']))
            ->assertOk()
            ->assertDontSee('Seller Sku Only Target');
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

    public function test_seller_can_sell_energy_token_without_product_stock_movement(): void
    {
        $seller = $this->createSellerWithManager();

        $response = $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'C1',
            'amount' => 1200,
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('seller.tokens.create'));

        $sale = Sale::firstOrFail();

        $this->assertSame((float) 1200, (float) $sale->total);
        $this->assertSame('cash', $sale->payment_method);

        $token = $sale->items->first()->service_payload['token'] ?? '';
        $this->assertMatchesRegularExpression('/^\d{4} \d{4} \d{4} \d{4} \d{4}$/', $token);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => null,
            'service_name' => 'Token Energie',
            'quantity' => 1,
            'unit_price' => 1200,
            'subtotal' => 1200,
        ]);

        $this->assertDatabaseCount('stock_movements', 0);

        $historyResponse = $this->actingAs($seller)->get(route('seller.sales.history'));
        $historyResponse->assertOk()
            ->assertSee($sale->invoice_number)
            ->assertSee('1 200 FCFA');
    }

    public function test_seller_can_sell_energy_token_with_sms_fee_on_cash_payment(): void
    {
        $seller = $this->createSellerWithManager();

        $response = $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'C1',
            'amount' => 1200,
            'payment_method' => 'cash',
            'send_sms' => '1',
            'sms_phone' => '699123456',
        ]);

        $response->assertRedirect(route('seller.tokens.create'));

        $sale = Sale::with('items')->firstOrFail();
        $payload = $sale->items->first()->service_payload;

        $this->assertSame(1200.0, (float) $sale->subtotal);
        $this->assertSame(1225.0, (float) $sale->total);
        $this->assertSame(1225.0, (float) $sale->amount_received);
        $this->assertSame('cash', $sale->payment_method);
        $this->assertTrue($payload['send_sms']);
        $this->assertSame('699123456', $payload['sms_phone']);
        $this->assertSame(25.0, (float) $payload['sms_fee']);
        $this->assertSame(0.0, (float) $payload['operator_fee']);
        $this->assertSame(1200.0, (float) $payload['token_amount']);
        $this->assertStringContainsString('SMS: 699123456', $sale->notes);
    }

    public function test_energy_token_mobile_payment_records_sale_directly_without_monetbil_transaction(): void
    {
        config(['services.socadel_token.fake_mode' => true]);

        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'C1',
            'amount' => 1200,
            'payment_method' => 'mobile_money',
            'customer_phone' => '690123456',
        ])->assertRedirect(route('seller.tokens.create'));

        $this->assertSame(1, Sale::count());
        $this->assertSame(0, PaymentTransaction::count());
        $this->assertDatabaseHas('sales', [
            'seller_id' => $seller->id,
            'payment_method' => 'card',
            'subtotal' => 1200,
            'total' => 1236,
        ]);
    }

    public function test_energy_token_sms_is_sent_with_avlytext_after_token_generation(): void
    {
        config([
            'services.socadel_token.fake_mode' => true,
            'services.avlytext.api_key' => 'test-avly-key',
            'services.avlytext.sender' => 'SmartCity',
        ]);

        Http::fake([
            'api.avlytext.com/v1/sms*' => Http::response([
                'id' => 'sms-provider-001',
                'cost' => 0.025,
                'parts' => 1,
            ]),
        ]);

        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'C1',
            'amount' => 1200,
            'payment_method' => 'cash',
            'send_sms' => '1',
            'sms_phone' => '699123456',
        ])->assertRedirect(route('seller.tokens.create'));

        $sale = Sale::with('items')->firstOrFail();
        $payload = $sale->items->first()->service_payload;

        $this->assertSame('sent', $payload['sms_status']);
        $this->assertSame('Orange', $payload['sms_operator']);
        $this->assertSame('sms-provider-001', $payload['sms_provider_id']);

        Http::assertSent(function ($request) use ($payload) {
            return $request->method() === 'POST'
                && str_starts_with($request->url(), 'https://api.avlytext.com/v1/sms?api_key=test-avly-key')
                && $request['sender'] === 'SmartCity'
                && $request['recipient'] === '+237699123456'
                && str_contains($request['text'], "\nC1\n")
                && str_contains($request['text'], 'Token: '.$payload['token'])
                && str_contains($request['text'], 'SMS: 25 F')
                && ! str_contains($request['text'], 'Compteur')
                && ! str_contains($request['text'], 'MERCI');
        });
    }

    public function test_energy_token_sms_uses_numeric_sender_for_mtn_numbers_when_configured(): void
    {
        config([
            'services.socadel_token.fake_mode' => true,
            'services.avlytext.api_key' => 'test-avly-key',
            'services.avlytext.sender' => 'SmartCity',
            'services.avlytext.sender_mtn' => '+237674352969',
        ]);

        Http::fake([
            'api.avlytext.com/v1/sms*' => Http::response([
                'id' => 'sms-provider-mtn-001',
                'cost' => 0.025,
                'parts' => 1,
            ]),
        ]);

        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'C1',
            'amount' => 1200,
            'payment_method' => 'cash',
            'send_sms' => '1',
            'sms_phone' => '677123456',
        ])->assertRedirect(route('seller.tokens.create'));

        $sale = Sale::with('items')->firstOrFail();
        $payload = $sale->items->first()->service_payload;

        $this->assertSame('sent', $payload['sms_status']);
        $this->assertSame('MTN', $payload['sms_operator']);
        $this->assertSame('sms-provider-mtn-001', $payload['sms_provider_id']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_starts_with($request->url(), 'https://api.avlytext.com/v1/sms?api_key=test-avly-key')
                && $request['sender'] === '+237674352969'
                && $request['recipient'] === '+237677123456';
        });
    }

    public function test_energy_token_sale_posts_token_amount_to_socadel_api(): void
    {
        config([
            'services.socadel_token.fake_mode' => false,
            'services.socadel_token.api_key' => 'test-socadel-key',
            'services.socadel_token.base_url' => 'https://www.smartcitydouala.com/api/v1',
            'services.socadel_token.endpoint' => '/token/socadel',
        ]);

        Http::fake([
            'www.smartcitydouala.com/api/v1/token/socadel' => Http::response([
                'status' => 'success',
                'token' => '1111 2222 3333 4444',
            ]),
        ]);

        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'C1',
            'amount' => 1200,
            'payment_method' => 'cash',
            'send_sms' => '1',
            'sms_phone' => '699123456',
        ])->assertRedirect(route('seller.tokens.create'));

        $sale = Sale::with('items')->firstOrFail();
        $payload = $sale->items->first()->service_payload;

        $this->assertSame('1111 2222 3333 4444', $payload['token']);
        $this->assertSame('socadel', $payload['api_provider']);
        $this->assertSame(1225.0, (float) $sale->total);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://www.smartcitydouala.com/api/v1/token/socadel'
                && in_array('application/json', $request->header('Accept'), true)
                && in_array('Bearer test-socadel-key', $request->header('Authorization'), true)
                && $request['project'] === 'SMARTCITY 1'
                && $request['room'] === 'C1'
                && $request['amount'] === '1200'
                && preg_match('/^TOKEN-\d{8}-[A-Z0-9]{16}$/', $request['transaction']) === 1
                && ! isset($request['operator_fee'])
                && ! isset($request['sms_fee'])
                && ! isset($request['total_amount'])
                && ! isset($request['secret_token']);
        });
    }

    public function test_energy_token_socadel_error_prevents_sale_creation(): void
    {
        config([
            'services.socadel_token.fake_mode' => false,
            'services.socadel_token.base_url' => 'https://www.smartcitydouala.com/api/v1',
            'services.socadel_token.endpoint' => '/token/socadel',
        ]);

        Http::fake([
            'www.smartcitydouala.com/api/v1/token/socadel' => Http::response([
                'status' => false,
                'error' => 'meter_not_set',
                'message' => '',
            ]),
        ]);

        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'NKOZOA',
            'room_number' => 'C1',
            'amount' => 1200,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors(['token_api' => 'Token non genere : meter_not_set']);

        $this->assertSame(0, Sale::count());
    }

    public function test_energy_token_mobile_payment_waits_for_monetbil_and_feeds_mobile_money_balance(): void
    {
        config(['services.monetbil.fake_mode' => true]);
        config(['services.monetbil.fake_result' => 'success']);

        $seller = $this->createSellerWithManager();

        $response = $this->actingAs($seller)->postJson(route('seller.tokens.mobile-payment.start'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'A10',
            'amount' => 2400,
            'payment_method' => 'mobile_money',
            'customer_phone' => '655123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', PaymentTransaction::STATUS_PENDING);

        $this->assertSame(0, Sale::count());

        $transaction = PaymentTransaction::firstOrFail();

        $this->actingAs($seller)
            ->postJson(route('seller.payments.check', $transaction))
            ->assertOk()
            ->assertJsonPath('status', PaymentTransaction::STATUS_SUCCESS);

        $sale = Sale::firstOrFail();

        $this->assertSame('card', $sale->payment_method);
        $this->assertSame('655123456', $sale->customer_phone);
        $this->assertSame(0.0, app(CashRegisterService::class)->balanceForSeller($seller));
        $this->assertSame(2472.0, app(CashRegisterService::class)->mobileMoneyBalanceForSeller($seller));

        $this->assertSame(2400.0, (float) $sale->subtotal);
        $this->assertSame(2472.0, (float) $sale->total);
        $this->assertSame(2472.0, (float) $sale->amount_received);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => null,
            'service_name' => 'Token Energie',
            'unit_price' => 2400,
            'subtotal' => 2400,
        ]);
    }

    public function test_energy_token_mobile_payment_posts_internal_reference_to_socadel_api(): void
    {
        config([
            'services.monetbil.fake_mode' => true,
            'services.monetbil.fake_result' => 'success',
            'services.socadel_token.fake_mode' => false,
            'services.socadel_token.api_key' => 'test-socadel-key',
            'services.socadel_token.base_url' => 'https://www.smartcitydouala.com/api/v1',
            'services.socadel_token.endpoint' => '/token/socadel',
        ]);

        Http::fake([
            'www.smartcitydouala.com/api/v1/token/socadel' => Http::response([
                'status' => 'success',
                'token' => '9999 8888 7777 6666',
            ]),
        ]);

        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->postJson(route('seller.tokens.mobile-payment.start'), [
            'meter_address' => 'NKOZOA',
            'room_number' => 'A10',
            'amount' => 2400,
            'payment_method' => 'mobile_money',
            'customer_phone' => '655123456',
        ])->assertOk();

        $transaction = PaymentTransaction::firstOrFail();

        $this->actingAs($seller)
            ->postJson(route('seller.payments.check', $transaction))
            ->assertOk()
            ->assertJsonPath('status', PaymentTransaction::STATUS_SUCCESS);

        Http::assertSent(function ($request) use ($transaction) {
            return $request->method() === 'POST'
                && $request->url() === 'https://www.smartcitydouala.com/api/v1/token/socadel'
                && $request['project'] === 'NKOZOA'
                && $request['room'] === 'A10'
                && $request['amount'] === '2400'
                && preg_match('/^TOKEN-\d{8}-[A-Z0-9]{16}$/', $request['transaction']) === 1
                && $request['transaction'] !== $transaction->reference
                && $request['transaction'] !== $transaction->monetbil_payment_id
                && in_array('Bearer test-socadel-key', $request->header('Authorization'), true)
                && ! isset($request['operator_fee'])
                && ! isset($request['sms_fee'])
                && ! isset($request['total_amount'])
                && ! isset($request['secret_token']);
        });
    }

    public function test_energy_token_mobile_payment_includes_sms_fee_for_monetbil_only(): void
    {
        config(['services.monetbil.fake_mode' => true]);
        config(['services.monetbil.fake_result' => 'success']);

        $seller = $this->createSellerWithManager();

        $response = $this->actingAs($seller)->postJson(route('seller.tokens.mobile-payment.start'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'A10',
            'amount' => 2400,
            'payment_method' => 'mobile_money',
            'customer_phone' => '655123456',
            'send_sms' => '1',
            'sms_phone' => '699123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('transaction.amount', 2400)
            ->assertJsonPath('transaction.operator_fee', 97)
            ->assertJsonPath('transaction.total_amount', 2497);

        $transaction = PaymentTransaction::firstOrFail();

        $this->actingAs($seller)
            ->postJson(route('seller.payments.check', $transaction))
            ->assertOk()
            ->assertJsonPath('status', PaymentTransaction::STATUS_SUCCESS);

        $sale = Sale::with('items')->firstOrFail();
        $payload = $sale->items->first()->service_payload;

        $this->assertSame(0.0, app(CashRegisterService::class)->balanceForSeller($seller));
        $this->assertSame(2497.0, app(CashRegisterService::class)->mobileMoneyBalanceForSeller($seller));
        $this->assertSame(2400.0, (float) $sale->subtotal);
        $this->assertSame(2497.0, (float) $sale->total);
        $this->assertSame(2497.0, (float) $sale->amount_received);
        $this->assertSame(2400.0, (float) $payload['token_amount']);
        $this->assertSame(72.0, (float) $payload['operator_fee']);
        $this->assertSame(25.0, (float) $payload['sms_fee']);
        $this->assertTrue($payload['send_sms']);
        $this->assertSame('699123456', $payload['sms_phone']);
    }

    public function test_energy_token_sms_phone_is_required_when_sms_is_checked(): void
    {
        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'C1',
            'amount' => 1200,
            'payment_method' => 'cash',
            'send_sms' => '1',
        ])->assertSessionHasErrors(['sms_phone']);

        $this->actingAs($seller)->postJson(route('seller.tokens.mobile-payment.start'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'A10',
            'amount' => 2400,
            'payment_method' => 'mobile_money',
            'customer_phone' => '655123456',
            'send_sms' => '1',
        ])->assertJsonValidationErrors(['sms_phone']);

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_energy_token_amount_must_be_multiple_of_120(): void
    {
        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'C1',
            'amount' => 1250,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors(['amount']);

        $this->actingAs($seller)->postJson(route('seller.tokens.mobile-payment.start'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'A10',
            'amount' => 1250,
            'payment_method' => 'mobile_money',
            'customer_phone' => '655123456',
        ])->assertJsonValidationErrors(['amount']);

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_energy_token_recent_check_returns_false_without_recent_payment(): void
    {
        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)
            ->postJson(route('seller.tokens.check-recent'), [
                'meter_address' => 'SMARTCITY 1',
                'room_number' => 'A10',
            ])
            ->assertOk()
            ->assertJsonPath('recent', false);
    }

    public function test_energy_token_recent_check_returns_message_for_same_address_and_room(): void
    {
        $seller = $this->createSellerWithManager();

        $sale = Sale::create([
            'seller_id' => $seller->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'subtotal' => 1200,
            'total' => 1200,
            'payment_method' => 'cash',
            'amount_received' => 1200,
            'change_given' => 0,
            'customer_name' => 'A10',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => null,
            'service_name' => 'Token Energie',
            'service_payload' => [
                'meter_address' => 'SMARTCITY 1',
                'room_number' => 'A10',
                'token_amount' => 1200,
            ],
            'quantity' => 1,
            'unit_price' => 1200,
            'subtotal' => 1200,
        ]);

        $this->actingAs($seller)
            ->postJson(route('seller.tokens.check-recent'), [
                'meter_address' => 'SMARTCITY 1',
                'room_number' => 'A10',
            ])
            ->assertOk()
            ->assertJsonPath('recent', true)
            ->assertJsonPath('message', 'A10 de SMARTCITY 1 a payé un token de 1 200 FCFA il y a moins de 48h. Voulez-vous vraiment lui vendre encore ?');
    }

    public function test_energy_token_recent_sale_requires_confirmation_before_socadel_call(): void
    {
        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'A10',
            'amount' => 1200,
            'payment_method' => 'cash',
        ])->assertRedirect(route('seller.tokens.create'));

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'A10',
            'amount' => 2400,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors(['token_recent']);

        $this->assertSame(1, Sale::count());

        $this->actingAs($seller)->post(route('seller.tokens.store'), [
            'meter_address' => 'SMARTCITY 1',
            'room_number' => 'A10',
            'amount' => 2400,
            'payment_method' => 'cash',
            'confirmed_recent_token' => '1',
        ])->assertRedirect(route('seller.tokens.create'));

        $this->assertSame(2, Sale::count());
    }

    public function test_energy_token_sms_message_matches_expected_format(): void
    {
        Carbon::setTestNow('2026-05-26 18:07:00');

        $reflection = new \ReflectionClass(AvlyTextSmsService::class);
        $method = $reflection->getMethod('tokenMessage');
        $method->setAccessible(true);
        $message = $method->invoke(app(AvlyTextSmsService::class), [
            'room_number' => 'APPART 15',
            'kwh' => 10,
            'token_amount' => 1200,
            'token' => '42512254952496477078',
            'sms_fee' => 25,
            'operator_fee' => 36.75,
            'payment_method' => 'mobile_money',
        ]);

        $this->assertSame(
            "26/05/2026/ 18:07\nPdv/SC1\nAPPART 15\n10KWH/1,200 F\nToken: 4251 2254 9524 9647 7078\nSMS: 25 F\nFrais Momo: 36.75 F",
            $message
        );

        Carbon::setTestNow();
    }

    public function test_manager_top_products_page_orders_products_by_quantity(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = $seller->creator;
        $category = Category::create([
            'name' => 'Pain',
            'created_by' => $manager->id,
        ]);
        $lowProduct = Product::create([
            'name' => 'Produit faible',
            'sku' => 'LOW',
            'category_id' => $category->id,
            'purchase_price' => 50,
            'selling_price' => 100,
            'quantity' => 50,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);
        $topProduct = Product::create([
            'name' => 'Produit fort',
            'sku' => 'TOP',
            'category_id' => $category->id,
            'purchase_price' => 60,
            'selling_price' => 120,
            'quantity' => 50,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);
        $sale = Sale::create([
            'seller_id' => $seller->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'subtotal' => 1300,
            'total' => 1300,
            'payment_method' => 'cash',
            'amount_received' => 1300,
            'change_given' => 0,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $lowProduct->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $topProduct->id,
            'quantity' => 10,
            'unit_price' => 120,
            'subtotal' => 1200,
        ]);

        $response = $this->actingAs($manager)->get(route('manager.sales.top-products'));

        $response->assertOk()
            ->assertSee('Top produits')
            ->assertSeeInOrder(['Produit fort', 'Produit faible'])
            ->assertSee('10 piece')
            ->assertSee('1 piece');
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
