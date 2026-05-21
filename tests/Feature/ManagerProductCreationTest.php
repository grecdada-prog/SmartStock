<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagerProductCreationTest extends TestCase
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

    public function test_manager_can_create_product_without_sku(): void
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $category = Category::create([
            'name' => 'Boulangerie',
            'created_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->post(route('manager.products.store'), [
            'name' => 'Pain complet',
            'category_id' => $category->id,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.index'));

        $product = Product::firstOrFail();

        $this->assertSame('Pain complet', $product->name);
        $this->assertNotEmpty($product->sku);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{3}-\d{6}-\d{4}$/', $product->sku);
    }

    public function test_manager_can_restock_product_with_new_stock_barcode(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $response = $this->actingAs($manager)->post(route('manager.stock.restock.store'), [
            'product_id' => $product->id,
            'quantity' => 12,
            'purchase_price' => 500,
            'selling_price' => 750,
            'barcode' => '6 9455 85 0039 13',
        ]);

        $response->assertRedirect(route('manager.stock.index'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 12,
            'barcode' => '6 9455 85 0039 13',
            'reference' => null,
        ]);

        $this->assertSame(12, $product->fresh()->quantity);
        $this->assertSame('6 9455 85 0039 13', StockMovement::firstOrFail()->barcode);
    }

    public function test_manager_can_restock_product_with_unspaced_barcode(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $response = $this->actingAs($manager)->post(route('manager.stock.restock.store'), [
            'product_id' => $product->id,
            'quantity' => 12,
            'purchase_price' => 500,
            'selling_price' => 750,
            'barcode' => '6945585003913',
        ]);

        $response->assertRedirect(route('manager.stock.index'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'barcode' => '6 9455 85 0039 13',
        ]);
    }

    public function test_manager_cannot_restock_product_with_more_than_13_barcode_digits(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $response = $this->actingAs($manager)->from(route('manager.stock.restock'))->post(route('manager.stock.restock.store'), [
            'product_id' => $product->id,
            'quantity' => 12,
            'purchase_price' => 500,
            'selling_price' => 750,
            'barcode' => '69455850039134',
        ]);

        $response->assertRedirect(route('manager.stock.restock'));
        $response->assertSessionHasErrors('barcode');
        $this->assertDatabaseCount('stock_movements', 0);
    }

    private function makeManagerProduct(): array
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $manager->id,
        ]);

        $product = Product::create([
            'name' => 'Jus ananas',
            'sku' => 'JUS-260521-0001',
            'category_id' => $category->id,
            'purchase_price' => 0,
            'selling_price' => 0,
            'quantity' => 0,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        return [$manager, $product];
    }
}
