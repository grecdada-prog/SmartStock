<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CashBalanceAdjustment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerDirectRestockTest extends TestCase
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

    public function test_seller_sees_direct_restock_form_and_only_eligible_manager_products(): void
    {
        [$seller, $eligible, $notEligible, $otherProduct] = $this->makeDirectRestockProducts();

        $this->actingAs($seller)
            ->get(route('seller.direct-restock.create'))
            ->assertOk()
            ->assertSee('Appro direct');

        $this->actingAs($seller)
            ->getJson(route('seller.direct-restock.products', ['q' => 'Sav']))
            ->assertOk()
            ->assertJsonPath('products.0.id', $eligible->id)
            ->assertJsonCount(1, 'products')
            ->assertJsonMissing(['id' => $notEligible->id])
            ->assertJsonMissing(['id' => $otherProduct->id]);
    }

    public function test_seller_can_direct_restock_eligible_product(): void
    {
        [$seller, $product] = $this->makeDirectRestockProducts();

        CashBalanceAdjustment::create([
            'seller_id' => $seller->id,
            'manager_id' => $seller->id,
            'type' => 'add',
            'balance_type' => 'cash',
            'amount' => 5000,
            'reason' => 'Fond appro direct',
        ]);

        $this->actingAs($seller)->post(route('seller.direct-restock.store'), [
            'product_id' => $product->id,
            'quantity' => 8,
            'purchase_price' => 500,
            'selling_price' => 750,
            'non_perishable' => '1',
            'cash_type' => 'cash',
        ])->assertRedirect(route('seller.direct-restock.create'));

        $this->assertSame(10, $product->fresh()->quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 8,
            'quantity_before' => 2,
            'quantity_after' => 10,
            'reason' => 'Appro direct',
            'user_id' => $seller->id,
        ]);

        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'type' => 'withdraw',
            'balance_type' => 'cash',
            'amount' => 4000,
            'source' => 'restock',
        ]);
    }

    public function test_direct_restock_debits_selected_mobile_money_register_only(): void
    {
        [$seller, $product] = $this->makeDirectRestockProducts();

        CashBalanceAdjustment::create([
            'seller_id' => $seller->id,
            'manager_id' => $seller->id,
            'type' => 'add',
            'balance_type' => 'cash',
            'amount' => 5000,
            'reason' => 'Fond cash',
        ]);
        CashBalanceAdjustment::create([
            'seller_id' => $seller->id,
            'manager_id' => $seller->id,
            'type' => 'add',
            'balance_type' => 'mobile_money',
            'amount' => 5000,
            'reason' => 'Fond MOMO',
        ]);

        $this->actingAs($seller)->post(route('seller.direct-restock.store'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'purchase_price' => 300,
            'selling_price' => 450,
            'non_perishable' => '1',
            'cash_type' => 'mobile_money',
        ])->assertRedirect(route('seller.direct-restock.create'));

        $this->assertSame(7, $product->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reason' => 'Appro direct',
            'user_id' => $seller->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'type' => 'withdraw',
            'balance_type' => 'mobile_money',
            'amount' => 1500,
            'source' => 'restock',
        ]);
        $this->assertDatabaseMissing('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'type' => 'withdraw',
            'balance_type' => 'cash',
            'amount' => 1500,
            'source' => 'restock',
        ]);
    }

    public function test_seller_cannot_direct_restock_non_eligible_or_other_manager_product(): void
    {
        [$seller, $eligible, $notEligible, $otherProduct] = $this->makeDirectRestockProducts();

        $this->actingAs($seller)->post(route('seller.direct-restock.store'), [
            'product_id' => $notEligible->id,
            'quantity' => 8,
            'purchase_price' => 500,
            'selling_price' => 750,
            'non_perishable' => '1',
            'cash_type' => 'cash',
        ])->assertNotFound();

        $this->actingAs($seller)->post(route('seller.direct-restock.store'), [
            'product_id' => $otherProduct->id,
            'quantity' => 8,
            'purchase_price' => 500,
            'selling_price' => 750,
            'non_perishable' => '1',
            'cash_type' => 'cash',
        ])->assertNotFound();

        $this->assertSame(2, $eligible->fresh()->quantity);
        $this->assertSame(2, $notEligible->fresh()->quantity);
        $this->assertSame(2, $otherProduct->fresh()->quantity);
    }

    public function test_direct_restock_with_insufficient_funds_does_not_touch_stock(): void
    {
        [$seller, $product] = $this->makeDirectRestockProducts();

        $this->actingAs($seller)->post(route('seller.direct-restock.store'), [
            'product_id' => $product->id,
            'quantity' => 8,
            'purchase_price' => 500,
            'selling_price' => 750,
            'non_perishable' => '1',
            'cash_type' => 'cash',
        ])->assertSessionHasErrors('cash_type');

        $this->assertSame(2, $product->fresh()->quantity);
        $this->assertSame(0, StockMovement::where('reason', 'Appro direct')->count());
        $this->assertSame(0, CashBalanceAdjustment::where('source', 'restock')->count());
    }

    private function makeDirectRestockProducts(): array
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $seller = User::factory()->create(['created_by' => $manager->id]);
        $seller->assignRole('seller');

        $otherManager = User::factory()->create();
        $otherManager->assignRole('manager');

        $category = Category::create([
            'name' => 'Epicerie',
            'created_by' => $manager->id,
        ]);

        $otherCategory = Category::create([
            'name' => 'Autre',
            'created_by' => $otherManager->id,
        ]);

        $eligible = $this->makeProduct($category, $manager, 'Savon direct', true);
        $notEligible = $this->makeProduct($category, $manager, 'Savon bloque', false);
        $otherProduct = $this->makeProduct($otherCategory, $otherManager, 'Savon autre', true);

        return [$seller, $eligible, $notEligible, $otherProduct];
    }

    private function makeProduct(Category $category, User $manager, string $name, bool $eligible): Product
    {
        return Product::create([
            'name' => $name,
            'sku' => str($name)->ascii()->upper()->replace(' ', '-')->toString(),
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 2,
            'alert_quantity' => 1,
            'unit' => 'piece',
            'is_active' => true,
            'is_direct_restock_eligible' => $eligible,
            'created_by' => $manager->id,
        ]);
    }
}
