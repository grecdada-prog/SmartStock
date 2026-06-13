<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\PreventDirectAccess;
use App\Http\Middleware\SingleSessionMiddleware;
use App\Models\AiAnalysisSnapshot;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagerAiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PreventDirectAccess::class,
            SingleSessionMiddleware::class,
            CheckInactivity::class,
            CheckUserActive::class,
        ]);
    }

    public function test_manager_ai_assistant_page_renders_business_insights(): void
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $seller = User::factory()->create([
            'created_by' => $manager->id,
            'is_active' => true,
        ]);
        $seller->assignRole('seller');

        $underperformingSeller = User::factory()->create([
            'name' => 'Vendeur en baisse',
            'created_by' => $manager->id,
            'is_active' => true,
        ]);
        $underperformingSeller->assignRole('seller');

        $category = Category::create([
            'name' => 'Epicerie',
            'created_by' => $manager->id,
        ]);

        $product = Product::create([
            'name' => 'Sucre 1kg',
            'sku' => 'SUC-001',
            'category_id' => $category->id,
            'purchase_price' => 500,
            'selling_price' => 750,
            'quantity' => 3,
            'alert_quantity' => 5,
            'unit' => 'pcs',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-AI-0001',
            'seller_id' => $seller->id,
            'subtotal' => 7500,
            'tax' => 0,
            'discount' => 0,
            'total' => 7500,
            'amount_received' => 7500,
            'change_given' => 0,
            'payment_method' => 'cash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 750,
            'subtotal' => 7500,
        ]);

        foreach ([1, 2, 3] as $daysAgo) {
            $previousSale = Sale::create([
                'invoice_number' => 'INV-DROP-000'.$daysAgo,
                'seller_id' => $underperformingSeller->id,
                'subtotal' => 7000,
                'tax' => 0,
                'discount' => 0,
                'total' => 7000,
                'amount_received' => 7000,
                'change_given' => 0,
                'payment_method' => 'cash',
            ]);
            $previousSale->forceFill([
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ])->save();
        }

        StockMovement::create([
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 20,
            'quantity_before' => 0,
            'quantity_after' => 20,
            'purchase_price' => 500,
            'selling_price' => 750,
            'remaining_quantity' => 3,
            'batch_code' => 'LOT-AI-001',
            'is_perishable' => true,
            'expiration_date' => today()->addDays(2),
            'reason' => 'Lot test IA',
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('manager.ai-assistant.index'))
            ->assertOk()
            ->assertSee('SmartStore AI Assistant')
            ->assertSee('Sucre 1kg')
            ->assertSee('Previsions de rupture')
            ->assertSee('Methode IA explicable')
            ->assertSee('Confiance')
            ->assertSee('Historique court des analyses IA')
            ->assertSee('Limites et fiabilite')
            ->assertSee('Reapprovisionner')
            ->assertSee('source=ai', false)
            ->assertSee('Baisse inhabituelle par vendeur')
            ->assertSee('Anomalies et conseils IA')
            ->assertSee('Lots proches de la peremption');

        $this->assertDatabaseHas('ai_analysis_snapshots', [
            'manager_id' => $manager->id,
            'period_days' => 30,
            'target_days' => 15,
        ]);

        $snapshot = AiAnalysisSnapshot::where('manager_id', $manager->id)->latest('id')->first();
        $this->assertNotNull($snapshot);
        $this->assertNotEmpty($snapshot->stock_predictions);
        $this->assertNotNull(collect($snapshot->anomalies)->firstWhere('type', 'seller_revenue_drop'));

        $this->actingAs($manager)
            ->get(route('manager.stock.restock', [
                'product_id' => $product->id,
                'quantity' => 12,
                'source' => 'ai',
            ]))
            ->assertOk()
            ->assertSee('Sucre 1kg')
            ->assertSee('value="12"', false);

        $this->actingAs($manager)
            ->get(route('manager.ai-assistant.export-pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'smartstore_ai_report_exported',
            'model' => 'SmartStoreAiAssistant',
        ]);
    }
}
