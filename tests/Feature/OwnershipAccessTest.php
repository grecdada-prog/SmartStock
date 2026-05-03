<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnershipAccessTest extends TestCase
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

    public function test_manager_cannot_access_another_managers_resources(): void
    {
        [$managerA, $managerB, $sellerB, $categoryB, $productB] = $this->createDataset();

        $this->actingAs($managerA)
            ->get(route('manager.categories.show', $categoryB))
            ->assertForbidden();

        $this->actingAs($managerA)
            ->get(route('manager.products.show', $productB))
            ->assertForbidden();

        $this->actingAs($managerA)
            ->get(route('manager.sellers.show', $sellerB))
            ->assertForbidden();

        $this->actingAs($managerA)
            ->post(route('manager.products.toggle-status', $productB))
            ->assertForbidden();

        $this->assertSame($managerB->id, $productB->fresh()->created_by);
    }

    public function test_seller_cannot_access_another_sellers_sale_or_product(): void
    {
        [, , $sellerB, , $productB, $sellerA, $saleB] = $this->createDataset();

        $this->actingAs($sellerA)
            ->get(route('seller.products.show', $productB))
            ->assertNotFound();

        $this->actingAs($sellerA)
            ->get(route('seller.sales.show', $saleB))
            ->assertForbidden();

        $this->assertSame($sellerB->id, $saleB->seller_id);
    }

    private function createDataset(): array
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $managerA = User::factory()->create();
        $managerA->assignRole('manager');

        $managerB = User::factory()->create();
        $managerB->assignRole('manager');

        $sellerA = User::factory()->create(['created_by' => $managerA->id]);
        $sellerA->assignRole('seller');

        $sellerB = User::factory()->create(['created_by' => $managerB->id]);
        $sellerB->assignRole('seller');

        $categoryB = Category::create([
            'name' => 'Categorie B',
            'created_by' => $managerB->id,
        ]);

        $productB = Product::create([
            'name' => 'Produit B',
            'sku' => 'OWN-B-001',
            'category_id' => $categoryB->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'quantity' => 10,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $managerB->id,
        ]);

        $saleB = Sale::create([
            'invoice_number' => 'INV-OWN-B-0001',
            'seller_id' => $sellerB->id,
            'subtotal' => 150,
            'tax' => 0,
            'discount' => 0,
            'total' => 150,
            'amount_received' => 150,
            'change_given' => 0,
            'payment_method' => 'cash',
        ]);

        return [$managerA, $managerB, $sellerB, $categoryB, $productB, $sellerA, $saleB];
    }
}
