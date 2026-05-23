<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
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

    public function test_manager_can_create_product_with_barcode(): void
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
            'barcode' => '6945585003913',
            'category_id' => $category->id,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Pain complet',
            'barcode' => '6945585003913',
        ]);
    }

    public function test_manager_can_create_product_with_variable_length_barcode(): void
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $category = Category::create([
            'name' => 'Boulangerie',
            'created_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->from(route('manager.products.create'))->post(route('manager.products.store'), [
            'name' => 'Pain complet',
            'barcode' => '69455850039134',
            'category_id' => $category->id,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Pain complet',
            'barcode' => '69455850039134',
        ]);
    }

    public function test_manager_can_restock_product_without_barcode(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $response = $this->actingAs($manager)->post(route('manager.stock.restock.store'), [
            'product_id' => $product->id,
            'quantity' => 12,
            'purchase_price' => 500,
            'selling_price' => 750,
        ]);

        $response->assertRedirect(route('manager.stock.index'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 12,
            'reference' => null,
        ]);

        $this->assertSame(12, $product->fresh()->quantity);
    }

    public function test_manager_product_and_stock_search_use_barcode_not_sku(): void
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $category = Category::create([
            'name' => 'Epicerie',
            'created_by' => $manager->id,
        ]);

        Product::create([
            'name' => 'Barcode Target',
            'sku' => 'BARCODE-TARGET-SKU',
            'barcode' => '6 9455 85 0039 13',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 5,
            'alert_quantity' => 1,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        Product::create([
            'name' => 'Sku Only Target',
            'sku' => 'SKU-ONLY-SEARCH',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 5,
            'alert_quantity' => 1,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('manager.products.index', ['search' => '6945585003913']))
            ->assertOk()
            ->assertSee('Barcode Target')
            ->assertDontSee('Sku Only Target');

        $this->actingAs($manager)
            ->get(route('manager.products.index', ['search' => 'SKU-ONLY-SEARCH']))
            ->assertOk()
            ->assertDontSee('Sku Only Target');

        $this->actingAs($manager)
            ->get(route('manager.stock.index', ['search' => '6945585003913']))
            ->assertOk()
            ->assertSee('Barcode Target');

        $this->actingAs($manager)
            ->get(route('manager.stock.index', ['search' => 'SKU-ONLY-SEARCH']))
            ->assertOk()
            ->assertSee('Aucun produit');
    }

    public function test_manager_product_quick_create_modal_contains_barcode_field(): void
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        Category::create([
            'name' => 'Epicerie',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('manager.products.index'))
            ->assertOk()
            ->assertSee('id="modal_barcode"', false)
            ->assertSee('name="barcode"', false);
    }

    public function test_manager_cannot_update_product_sku_or_barcode(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $product->update([
            'sku' => 'ORIGINAL-SKU',
            'barcode' => '11012035024090',
        ]);

        $response = $this->actingAs($manager)->put(route('manager.products.update', $product), [
            'name' => 'Jus ananas modifie',
            'sku' => 'CHANGED-SKU',
            'barcode' => '6970155170122',
            'category_id' => $product->category_id,
            'alert_quantity' => 8,
            'unit' => 'carton',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.index'));

        $product->refresh();

        $this->assertSame('Jus ananas modifie', $product->name);
        $this->assertSame('ORIGINAL-SKU', $product->sku);
        $this->assertSame('11012035024090', $product->barcode);
        $this->assertSame(8, $product->alert_quantity);
        $this->assertSame('carton', $product->unit);
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
