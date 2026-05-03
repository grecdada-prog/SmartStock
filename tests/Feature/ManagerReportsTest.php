<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagerReportsTest extends TestCase
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

    public function test_manager_report_pages_render(): void
    {
        [$manager] = $this->createManagerDataset();

        $this->actingAs($manager)
            ->get(route('manager.reports.sales'))
            ->assertOk()
            ->assertSee('Rapport des ventes')
            ->assertSee('Recette filtree')
            ->assertSee('Recette du jour')
            ->assertDontSee('CA total')
            ->assertDontSee('Ce mois');

        $this->actingAs($manager)
            ->get(route('manager.reports.activity'))
            ->assertOk()
            ->assertSee('Rapport activite');

        $this->actingAs($manager)
            ->get(route('manager.reports.stock'))
            ->assertOk()
            ->assertSee('Rapport stock');
    }

    private function createManagerDataset(): array
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $seller = User::factory()->create([
            'created_by' => $manager->id,
        ]);
        $seller->assignRole('seller');

        $category = Category::create([
            'name' => 'Categorie test',
            'created_by' => $manager->id,
        ]);

        $product = Product::create([
            'name' => 'Produit test',
            'sku' => 'RPT-001',
            'category_id' => $category->id,
            'purchase_price' => 500,
            'selling_price' => 750,
            'quantity' => 8,
            'alert_quantity' => 3,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-TEST-0001',
            'seller_id' => $seller->id,
            'subtotal' => 750,
            'tax' => 0,
            'discount' => 0,
            'total' => 750,
            'amount_received' => 1000,
            'change_given' => 250,
            'payment_method' => 'cash',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 750,
            'subtotal' => 750,
        ]);

        return [$manager, $seller, $product, $sale];
    }
}
