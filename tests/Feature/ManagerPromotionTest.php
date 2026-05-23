<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPromotion;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagerPromotionTest extends TestCase
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

    public function test_manager_can_create_update_suspend_and_delete_a_promotion(): void
    {
        $manager = $this->manager();
        $product = $this->productForManager($manager, sellingPrice: 1000, quantity: 20);

        $this->actingAs($manager)
            ->post(route('manager.promotions.store'), [
                'product_id' => $product->id,
                'name' => 'Pack special',
                'promotion_price' => 750,
                'min_quantity' => 3,
                'status' => ProductPromotion::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('manager.promotions.index'));

        $promotion = ProductPromotion::firstOrFail();

        $this->assertDatabaseHas('product_promotions', [
            'id' => $promotion->id,
            'product_id' => $product->id,
            'manager_id' => $manager->id,
            'name' => 'Pack special',
            'promotion_price' => 750,
            'min_quantity' => 3,
            'status' => ProductPromotion::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)
            ->put(route('manager.promotions.update', $promotion), [
                'product_id' => $product->id,
                'name' => 'Pack weekend',
                'promotion_price' => 700,
                'min_quantity' => 4,
                'status' => ProductPromotion::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('manager.promotions.index'));

        $this->assertDatabaseHas('product_promotions', [
            'id' => $promotion->id,
            'name' => 'Pack weekend',
            'promotion_price' => 700,
            'min_quantity' => 4,
        ]);

        $this->actingAs($manager)
            ->post(route('manager.promotions.toggle-status', $promotion))
            ->assertRedirect();

        $this->assertDatabaseHas('product_promotions', [
            'id' => $promotion->id,
            'status' => ProductPromotion::STATUS_SUSPENDED,
        ]);

        $this->actingAs($manager)
            ->delete(route('manager.promotions.destroy', $promotion))
            ->assertRedirect(route('manager.promotions.index'));

        $this->assertDatabaseMissing('product_promotions', [
            'id' => $promotion->id,
        ]);
    }

    public function test_manager_promotion_page_displays_current_batch_prices(): void
    {
        $manager = $this->manager();
        $product = $this->productForManager($manager, sellingPrice: 1000, quantity: 8);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 8,
            'quantity_before' => 0,
            'quantity_after' => 8,
            'purchase_price' => 600,
            'selling_price' => 900,
            'remaining_quantity' => 8,
            'batch_code' => 'LOT-PROMO',
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('manager.promotions.create'))
            ->assertOk()
            ->assertSee('Prix actuels du produit')
            ->assertSee('LOT-PROMO')
            ->assertSee('900');
    }

    public function test_pos_products_endpoint_exposes_active_promotion(): void
    {
        [$manager, $seller] = $this->managerAndSellerWithOpenRegister();
        $product = $this->productForManager($manager, sellingPrice: 1000, quantity: 10);

        $promotion = ProductPromotion::create([
            'product_id' => $product->id,
            'manager_id' => $manager->id,
            'name' => 'Promo carton',
            'promotion_price' => 800,
            'min_quantity' => 3,
            'status' => ProductPromotion::STATUS_ACTIVE,
        ]);

        $this->actingAs($seller)
            ->getJson(route('seller.pos.products'))
            ->assertOk()
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.active_promotion.id', $promotion->id)
            ->assertJsonPath('products.0.active_promotion.promotion_price', '800.00')
            ->assertJsonPath('products.0.active_promotion.min_quantity', 3);
    }

    public function test_seller_sale_applies_promotion_only_when_requested_and_eligible(): void
    {
        [$manager, $seller] = $this->managerAndSellerWithOpenRegister();
        $product = $this->productForManager($manager, sellingPrice: 1000, quantity: 10);

        $promotion = ProductPromotion::create([
            'product_id' => $product->id,
            'manager_id' => $manager->id,
            'name' => 'Promo trois pieces',
            'promotion_price' => 700,
            'min_quantity' => 3,
            'status' => ProductPromotion::STATUS_ACTIVE,
        ]);

        $this->actingAs($seller)
            ->postJson(route('seller.pos.sale'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 3,
                        'apply_promotion' => true,
                        'promotion_id' => $promotion->id,
                    ],
                ],
                'payment_method' => 'cash',
                'amount_received' => 3000,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', '2100.00')
            ->assertJsonPath('change', '900.00');

        $sale = Sale::firstOrFail();

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'promotion_id' => $promotion->id,
            'quantity' => 3,
            'unit_price' => 700,
            'original_unit_price' => 1000,
            'subtotal' => 2100,
            'discount_amount' => 900,
        ]);
    }

    public function test_seller_cannot_force_promotion_below_minimum_quantity(): void
    {
        [$manager, $seller] = $this->managerAndSellerWithOpenRegister();
        $product = $this->productForManager($manager, sellingPrice: 1000, quantity: 10);

        $promotion = ProductPromotion::create([
            'product_id' => $product->id,
            'manager_id' => $manager->id,
            'name' => 'Promo trois pieces',
            'promotion_price' => 700,
            'min_quantity' => 3,
            'status' => ProductPromotion::STATUS_ACTIVE,
        ]);

        $this->actingAs($seller)
            ->postJson(route('seller.pos.sale'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'apply_promotion' => true,
                        'promotion_id' => $promotion->id,
                    ],
                ],
                'payment_method' => 'cash',
                'amount_received' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(0, Sale::count());
    }

    private function manager(): User
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        return $manager;
    }

    private function managerAndSellerWithOpenRegister(): array
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $seller = User::factory()->create([
            'created_by' => $manager->id,
        ]);
        $seller->assignRole('seller');

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));

        return [$manager, $seller];
    }

    private function productForManager(User $manager, int $sellingPrice, int $quantity): Product
    {
        $category = Category::create([
            'name' => 'Epicerie',
            'created_by' => $manager->id,
        ]);

        return Product::create([
            'name' => 'Produit promo',
            'sku' => 'PROMO-'.$manager->id.'-'.$sellingPrice,
            'category_id' => $category->id,
            'purchase_price' => 500,
            'selling_price' => $sellingPrice,
            'quantity' => $quantity,
            'alert_quantity' => 2,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);
    }
}
