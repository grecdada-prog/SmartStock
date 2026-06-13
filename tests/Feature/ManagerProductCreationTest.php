<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPromotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_manager_product_search_queries_database_and_respects_scope(): void
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');
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

        $product = Product::create([
            'name' => 'Savon citron',
            'sku' => 'SAV-001',
            'barcode' => '6934567890123',
            'category_id' => $category->id,
            'purchase_price' => 500,
            'selling_price' => 750,
            'quantity' => 12,
            'alert_quantity' => 3,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        Product::create([
            'name' => 'Savon autre gerant',
            'sku' => 'SAV-OTHER',
            'barcode' => '6934567890999',
            'category_id' => $otherCategory->id,
            'purchase_price' => 500,
            'selling_price' => 750,
            'quantity' => 12,
            'alert_quantity' => 3,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $otherManager->id,
        ]);

        $this->actingAs($manager)
            ->getJson(route('manager.products.search', ['q' => 'Sav']))
            ->assertOk()
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonCount(1, 'products');

        $this->actingAs($manager)
            ->getJson(route('manager.products.search', ['q' => '693456']))
            ->assertOk()
            ->assertJsonPath('products.0.id', $product->id);
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

    public function test_manager_can_create_product_eligible_for_direct_restock(): void
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $category = Category::create([
            'name' => 'Boulangerie',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)->post(route('manager.products.store'), [
            'name' => 'Pain direct',
            'category_id' => $category->id,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => '1',
            'is_direct_restock_eligible' => '1',
        ])->assertRedirect(route('manager.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Pain direct',
            'is_direct_restock_eligible' => true,
            'created_by' => $manager->id,
        ]);
    }

    public function test_manager_can_update_product_direct_restock_eligibility(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $this->actingAs($manager)->put(route('manager.products.update', $product), [
            'name' => 'Jus ananas',
            'category_id' => $product->category_id,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => '1',
            'is_direct_restock_eligible' => '1',
        ])->assertRedirect(route('manager.products.index'));

        $this->assertTrue($product->fresh()->is_direct_restock_eligible);
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

    public function test_manager_can_create_product_with_thirteen_digit_barcode(): void
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
            'barcode' => '6945585003912',
            'category_id' => $category->id,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Pain complet',
            'barcode' => '6945585003912',
        ]);
    }

    public function test_manager_cannot_create_product_with_existing_barcode_and_gets_product_name(): void
    {
        [$manager, $product] = $this->makeManagerProduct();
        $product->update([
            'barcode' => '1101203502409',
        ]);

        $response = $this->actingAs($manager)->from(route('manager.products.create'))->post(route('manager.products.store'), [
            'name' => 'Autre jus',
            'barcode' => '1101203502409',
            'category_id' => $product->category_id,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.create'))
            ->assertSessionHasErrors(['barcode' => 'Code-barres deja existant chez vous.'])
            ->assertSessionHas('barcode_conflict_product_name', 'Jus ananas');

        $this->assertDatabaseMissing('products', [
            'name' => 'Autre jus',
            'barcode' => '1101203502409',
        ]);
    }

    public function test_two_managers_can_create_products_with_same_barcode(): void
    {
        [$manager, $product] = $this->makeManagerProduct();
        $product->update(['barcode' => '1234567890123']);

        $otherManager = User::factory()->create();
        $otherManager->assignRole('manager');
        $otherCategory = Category::create([
            'name' => 'Boissons autre',
            'created_by' => $otherManager->id,
        ]);

        $response = $this->actingAs($otherManager)->post(route('manager.products.store'), [
            'name' => 'Coca autre gerant',
            'barcode' => '1234567890123',
            'category_id' => $otherCategory->id,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Jus ananas',
            'barcode' => '1234567890123',
            'created_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('products', [
            'name' => 'Coca autre gerant',
            'barcode' => '1234567890123',
            'created_by' => $otherManager->id,
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
            'expiration_date' => today()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect(route('manager.stock.index'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 12,
            'reference' => null,
            'is_perishable' => true,
        ]);
        $this->assertSame(today()->addMonth()->toDateString(), $product->stockMovements()->firstOrFail()->expiration_date->toDateString());

        $this->assertSame(12, $product->fresh()->quantity);
    }

    public function test_manager_can_restock_many_times_without_hitting_hourly_throttle(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        for ($attempt = 1; $attempt <= 25; $attempt++) {
            $response = $this->actingAs($manager)->post(route('manager.stock.restock.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'purchase_price' => 500,
                'selling_price' => 750,
                'non_perishable' => '1',
            ]);

            $response->assertRedirect(route('manager.stock.index'));
        }

        $this->assertSame(25, $product->fresh()->quantity);
    }

    public function test_manager_restock_requires_expiration_date_unless_product_is_non_perishable(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $this->actingAs($manager)
            ->from(route('manager.stock.index'))
            ->post(route('manager.stock.restock.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'purchase_price' => 500,
                'selling_price' => 750,
            ])
            ->assertRedirect(route('manager.stock.index'))
            ->assertSessionHasErrors('expiration_date');

        $this->actingAs($manager)
            ->post(route('manager.stock.restock.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'purchase_price' => 500,
                'selling_price' => 750,
                'non_perishable' => '1',
            ])
            ->assertRedirect(route('manager.stock.index'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => 5,
            'is_perishable' => false,
            'expiration_date' => null,
        ]);
    }

    public function test_manager_can_update_product_promotion_prices_during_restock(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $promotion = ProductPromotion::create([
            'product_id' => $product->id,
            'manager_id' => $manager->id,
            'name' => 'Promo volume',
            'promotion_price' => 700,
            'min_quantity' => 2,
            'status' => ProductPromotion::STATUS_ACTIVE,
        ]);

        $otherProduct = Product::create([
            'name' => 'Autre produit',
            'sku' => 'OTHER-PROMO',
            'category_id' => $product->category_id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 5,
            'alert_quantity' => 1,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        $otherPromotion = ProductPromotion::create([
            'product_id' => $otherProduct->id,
            'manager_id' => $manager->id,
            'name' => 'Promo autre',
            'promotion_price' => 150,
            'min_quantity' => 2,
            'status' => ProductPromotion::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)
            ->post(route('manager.stock.restock.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'purchase_price' => 500,
                'selling_price' => 800,
                'non_perishable' => '1',
                'promotion_prices' => [
                    $promotion->id => 650,
                    $otherPromotion->id => 50,
                ],
            ])
            ->assertRedirect(route('manager.stock.index'));

        $this->assertSame('650.00', $promotion->fresh()->promotion_price);
        $this->assertSame('150.00', $otherPromotion->fresh()->promotion_price);
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
            ->assertSee('<style>[x-cloak]{display:none!important}</style>', false)
            ->assertSee('id="manager-products-page" data-silent-refresh x-data', false)
            ->assertSee('x-cloak class="px-4 sm:px-6 lg:px-8"', false)
            ->assertSee('data-disable-on-submit', false)
            ->assertSee('id="modal_barcode"', false)
            ->assertSee('name="barcode"', false);
    }

    public function test_manager_can_import_products_from_csv(): void
    {
        [$manager, $existingProduct] = $this->makeManagerProduct();
        $existingProduct->update(['barcode' => '123456789']);

        $path = tempnam(sys_get_temp_dir(), 'products-import').'.csv';
        file_put_contents($path, implode("\n", [
            'nom,code_barres,description,seuil_stock',
            'Produit importe,987654321,Description importee,4',
            'Doublon,123456789,Code deja utilise,2',
        ]));

        $file = new UploadedFile($path, 'produits.csv', 'text/csv', null, true);

        $this->actingAs($manager)
            ->post(route('manager.products.import'), [
                'products_file' => $file,
            ])
            ->assertRedirect(route('manager.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'Produit importe',
            'barcode' => '987654321',
            'description' => 'Description importee',
            'alert_quantity' => 4,
            'unit' => 'piece',
            'quantity' => 0,
            'purchase_price' => 0,
            'selling_price' => 0,
            'created_by' => $manager->id,
        ]);

        $this->assertDatabaseMissing('products', [
            'name' => 'Doublon',
            'barcode' => '123456789',
        ]);
    }

    public function test_manager_import_accepts_barcode_used_by_another_manager_but_rejects_own_duplicates(): void
    {
        [$manager, $existingProduct] = $this->makeManagerProduct();
        $existingProduct->update(['barcode' => '1112223334445']);

        $otherManager = User::factory()->create();
        $otherManager->assignRole('manager');
        $otherCategory = Category::create([
            'name' => 'Autre',
            'created_by' => $otherManager->id,
        ]);
        Product::create([
            'name' => 'Produit autre gerant',
            'sku' => 'OTHER-IMPORT-001',
            'barcode' => '9998887776665',
            'category_id' => $otherCategory->id,
            'purchase_price' => 0,
            'selling_price' => 0,
            'quantity' => 0,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $otherManager->id,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'products-import').'.csv';
        file_put_contents($path, implode("\n", [
            'nom,code_barres,description,seuil_stock',
            'Code autre gerant,9998887776665,Doit passer,4',
            'Doublon import,9998887776665,Doit etre ignore,4',
            'Doublon chez nous,1112223334445,Doit etre ignore,4',
        ]));

        $file = new UploadedFile($path, 'produits.csv', 'text/csv', null, true);

        $this->actingAs($manager)
            ->post(route('manager.products.import'), [
                'products_file' => $file,
            ])
            ->assertRedirect(route('manager.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'Code autre gerant',
            'barcode' => '9998887776665',
            'created_by' => $manager->id,
        ]);
        $this->assertDatabaseMissing('products', [
            'name' => 'Doublon import',
            'created_by' => $manager->id,
        ]);
        $this->assertDatabaseMissing('products', [
            'name' => 'Doublon chez nous',
            'created_by' => $manager->id,
        ]);
    }

    public function test_manager_can_update_product_barcode_but_not_sku(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $product->update([
            'sku' => 'ORIGINAL-SKU',
            'barcode' => '1101203502409',
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
        $this->assertSame('6970155170122', $product->barcode);
        $this->assertSame(8, $product->alert_quantity);
        $this->assertSame('carton', $product->unit);
    }

    public function test_manager_cannot_update_product_with_existing_barcode_and_gets_product_name(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $otherProduct = Product::create([
            'name' => 'Produit deja associe',
            'sku' => 'PRO-260521-0002',
            'barcode' => '6970155170122',
            'category_id' => $product->category_id,
            'purchase_price' => 0,
            'selling_price' => 0,
            'quantity' => 0,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->from(route('manager.products.edit', $product))->put(route('manager.products.update', $product), [
            'name' => 'Jus ananas modifie',
            'barcode' => $otherProduct->barcode,
            'category_id' => $product->category_id,
            'alert_quantity' => 8,
            'unit' => 'carton',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.edit', $product))
            ->assertSessionHasErrors(['barcode' => 'Code-barres deja existant chez vous.'])
            ->assertSessionHas('barcode_conflict_product_name', 'Produit deja associe');

        $this->assertNull($product->fresh()->barcode);
    }

    public function test_manager_can_update_product_with_barcode_used_by_another_manager(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $otherManager = User::factory()->create();
        $otherManager->assignRole('manager');
        $otherCategory = Category::create([
            'name' => 'Autre manager',
            'created_by' => $otherManager->id,
        ]);
        Product::create([
            'name' => 'Produit autre manager',
            'sku' => 'OTHER-MANAGER-001',
            'barcode' => '6970155170122',
            'category_id' => $otherCategory->id,
            'purchase_price' => 0,
            'selling_price' => 0,
            'quantity' => 0,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $otherManager->id,
        ]);

        $response = $this->actingAs($manager)->put(route('manager.products.update', $product), [
            'name' => 'Jus ananas modifie',
            'barcode' => '6970155170122',
            'category_id' => $product->category_id,
            'alert_quantity' => 8,
            'unit' => 'carton',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.products.index'));

        $this->assertSame('6970155170122', $product->fresh()->barcode);
    }

    public function test_modal_product_update_returns_success_message_for_parent_page(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $response = $this->actingAs($manager)->put(route('manager.products.update', ['product' => $product, 'modal' => 1]), [
            'name' => 'Jus ananas modifie',
            'barcode' => '6970155170122',
            'category_id' => $product->category_id,
            'alert_quantity' => 8,
            'unit' => 'carton',
            'is_active' => '1',
        ]);

        $response->assertOk()
            ->assertSee('Produit mis à jour avec succès.')
            ->assertSee('smartstore:modal-success');

        $this->assertSame('Jus ananas modifie', $product->fresh()->name);
    }

    public function test_manager_low_stock_page_shows_low_stock_before_out_of_stock(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $product->update([
            'name' => 'Produit epuise',
            'quantity' => 0,
            'alert_quantity' => 5,
        ]);

        Product::create([
            'name' => 'Produit stock faible',
            'sku' => 'LOW-260521-0002',
            'category_id' => $product->category_id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 2,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        Product::create([
            'name' => 'Produit normal',
            'sku' => 'NOR-260521-0003',
            'category_id' => $product->category_id,
            'purchase_price' => 100,
            'selling_price' => 200,
            'quantity' => 10,
            'alert_quantity' => 5,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->get(route('manager.stock.low-stock'));

        $response->assertOk()
            ->assertSee('Produit stock faible')
            ->assertSee('Produit epuise')
            ->assertDontSee('Produit normal');

        $content = $response->getContent();

        $this->assertLessThan(
            strpos($content, 'Produit epuise'),
            strpos($content, 'Produit stock faible')
        );
    }

    public function test_manager_delete_product_removes_barcode_from_database(): void
    {
        [$manager, $product] = $this->makeManagerProduct();

        $product->update([
            'barcode' => '604300002506',
        ]);

        $this->actingAs($manager)
            ->delete(route('manager.products.destroy', $product))
            ->assertRedirect(route('manager.products.index'));

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'barcode' => '604300002506',
        ]);

        $this->actingAs($manager)
            ->post(route('manager.products.store'), [
                'name' => 'Jus ananas recree',
                'barcode' => '604300002506',
                'category_id' => $product->category_id,
                'alert_quantity' => 5,
                'unit' => 'piece',
                'is_active' => '1',
            ])
            ->assertRedirect(route('manager.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Jus ananas recree',
            'barcode' => '604300002506',
            'created_by' => $manager->id,
        ]);
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
