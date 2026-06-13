<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerManagerIntegrationTest extends TestCase
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

    public function test_seller_sale_updates_manager_sales_stock_movements_and_activity_history(): void
    {
        [$manager, $seller] = $this->createManagerAndSeller();
        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $category = Category::create([
            'name' => 'Boissons',
            'created_by' => $manager->id,
        ]);
        $product = Product::create([
            'name' => 'Eau integration',
            'sku' => 'INT-EAU-001',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 250,
            'quantity' => 10,
            'alert_quantity' => 2,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 10,
            'quantity_before' => 0,
            'quantity_after' => 10,
            'purchase_price' => 100,
            'selling_price' => 250,
            'remaining_quantity' => 10,
            'batch_code' => 'LOT-INTEGRATION',
            'user_id' => $manager->id,
            'reason' => 'Stock initial integration',
        ]);

        $saleResponse = $this->actingAs($seller)->postJson(route('seller.pos.sale'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
            'payment_method' => 'cash',
            'amount_received' => 1000,
        ]);

        $saleResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', '750.00')
            ->assertJsonPath('change', '250.00');

        $sale = Sale::with(['items.product', 'seller'])->firstOrFail();

        $this->assertSame($seller->id, $sale->seller_id);
        $this->assertSame('750.00', $sale->total);
        $this->assertSame('1000.00', $sale->amount_received);
        $this->assertSame('250.00', $sale->change_given);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 7,
            'created_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $seller->id,
            'type' => 'out',
            'quantity' => 3,
            'quantity_before' => 10,
            'quantity_after' => 7,
            'reference' => "Vente #{$sale->invoice_number}",
            'reason' => 'Vente enregistree via POS | Lots: LOT-INTEGRATION:3',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'batch_code' => 'LOT-INTEGRATION',
            'remaining_quantity' => 7,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $seller->id,
            'action' => 'sale_created',
            'model' => 'Sale',
            'model_id' => $sale->id,
        ]);

        $this->actingAs($manager)->get(route('manager.sales'))
            ->assertOk()
            ->assertSee($sale->invoice_number)
            ->assertSee($seller->name)
            ->assertSee('750 FCFA')
            ->assertSee('Esp');

        $this->actingAs($manager)->get(route('manager.stock.index'))
            ->assertOk()
            ->assertSee('Eau integration')
            ->assertSee('INT-EAU-001');

        $this->actingAs($manager)->get(route('manager.stock.movements'))
            ->assertOk()
            ->assertSee('Eau integration')
            ->assertSee("Vente #{$sale->invoice_number}")
            ->assertSee($seller->name);

        $activityLog = ActivityLog::where('action', 'sale_created')->firstOrFail();

        $this->assertSame(1, ActivityLog::where('action', 'sale_created')->count());
        $this->assertSame($sale->invoice_number, $activityLog->properties['invoice_number']);
        $this->assertSame($seller->id, $activityLog->properties['seller_id']);
        $this->assertSame('cash', $activityLog->properties['payment_method']);
    }

    public function test_manager_delete_seller_removes_user_and_sales_data(): void
    {
        [$manager, $seller] = $this->createManagerAndSeller();

        $sale = Sale::create([
            'seller_id' => $seller->id,
            'invoice_number' => 'INV-DELETE-SELLER',
            'subtotal' => 1500,
            'total' => 1500,
            'payment_method' => 'cash',
            'amount_received' => 1500,
            'change_given' => 0,
        ]);

        ActivityLog::create([
            'user_id' => $seller->id,
            'action' => 'sale_created',
            'model' => 'Sale',
            'model_id' => $sale->id,
            'description' => 'Vente test',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($manager)
            ->delete(route('manager.sellers.destroy', $seller))
            ->assertRedirect(route('manager.sellers.index'));

        $this->assertDatabaseMissing('users', ['id' => $seller->id]);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('activity_logs', ['user_id' => $seller->id]);
    }

    private function createManagerAndSeller(): array
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $manager = User::factory()->create([
            'name' => 'Gerant Integration',
        ]);
        $manager->assignRole('manager');

        $seller = User::factory()->create([
            'name' => 'Vendeur Integration',
            'created_by' => $manager->id,
        ]);
        $seller->assignRole('seller');

        return [$manager, $seller];
    }
}
