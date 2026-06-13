<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\PreventDirectAccess;
use App\Http\Middleware\SingleSessionMiddleware;
use App\Exports\ProductsExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-25 10:30:00');

        $this->withoutMiddleware([
            PreventDirectAccess::class,
            SingleSessionMiddleware::class,
            CheckInactivity::class,
            CheckUserActive::class,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_super_admin_excel_exports_are_downloaded(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);
        Excel::fake();

        $superAdmin = User::where('email', 'nanguefyllias@gmail.com')->firstOrFail();

        $this->actingAs($superAdmin)->get(route('superadmin.users.export.excel'))->assertOk();
        Excel::assertDownloaded('utilisateurs_2026-04-25_10-30-00.xlsx');

        $this->actingAs($superAdmin)->get(route('superadmin.sales.export.excel'))->assertOk();
        Excel::assertDownloaded('ventes_2026-04-25_10-30-00.xlsx');

        $this->actingAs($superAdmin)->get(route('superadmin.activity-logs.export.excel'))->assertOk();
        Excel::assertDownloaded('logs_activite_2026-04-25_10-30-00.xlsx');

        $this->actingAs($superAdmin)->get(route('superadmin.products.export.excel'))->assertOk();
        Excel::assertDownloaded('produits_2026-04-25_10-30-00.xlsx');

        $this->actingAs($superAdmin)->get(route('superadmin.products.export.csv'))->assertOk();
        Excel::assertDownloaded('produits_2026-04-25_10-30-00.csv');
    }

    public function test_manager_excel_exports_are_downloaded(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);
        Excel::fake();

        $manager = User::where('email', 'bertholfyllias200@gmail.com')->firstOrFail();

        $this->actingAs($manager)->get(route('manager.sales.export.excel'))->assertOk();
        Excel::assertDownloaded('ventes_manager_2026-04-25_10-30-00.xlsx');

        $this->actingAs($manager)->get(route('manager.products.export.excel'))->assertOk();
        Excel::assertDownloaded('produits_manager_2026-04-25_10-30-00.xlsx');

        $this->actingAs($manager)->get(route('manager.products.export.csv'))->assertOk();
        Excel::assertDownloaded('produits_manager_2026-04-25_10-30-00.csv');

        $this->actingAs($manager)->get(route('manager.stock.restocks.export.excel', [
            'date_from' => '2026-04-25',
            'date_to' => '2026-04-25',
        ]))->assertOk();
        Excel::assertDownloaded('approvisionnements_2026-04-25_2026-04-25.xlsx');
    }

    public function test_products_export_includes_unit_and_category_columns(): void
    {
        $manager = User::factory()->create();
        $category = Category::create([
            'name' => 'Epicerie',
            'created_by' => $manager->id,
        ]);
        $product = Product::create([
            'name' => 'Pain',
            'sku' => 'PAIN-001',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'quantity' => 10,
            'alert_quantity' => 2,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        $export = new ProductsExport($manager->id, true);

        $this->assertSame(
            ['Nom', 'Code-barres', 'Description', 'Unite', 'Categorie', 'Seuil de stock'],
            $export->headings()
        );
        $this->assertSame(
            ['Pain', '-', '-', 'piece', 'Epicerie', 2],
            $export->map($product->fresh('category'))
        );
    }

    public function test_manager_restock_history_shows_value_filter_and_filtered_total(): void
    {
        Role::findOrCreate('manager');

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $category = Category::create([
            'name' => 'Boulangerie',
            'created_by' => $manager->id,
        ]);
        $recentProduct = Product::create([
            'name' => 'Pain recent',
            'sku' => 'RECENT',
            'category_id' => $category->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'quantity' => 100,
            'alert_quantity' => 10,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);
        $oldProduct = Product::create([
            'name' => 'Pain ancien',
            'sku' => 'OLD',
            'category_id' => $category->id,
            'purchase_price' => 999,
            'selling_price' => 1200,
            'quantity' => 1,
            'alert_quantity' => 1,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        $oldMovement = StockMovement::create([
            'product_id' => $oldProduct->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 1,
            'quantity_before' => 0,
            'quantity_after' => 1,
            'remaining_quantity' => 1,
            'purchase_price' => 999,
            'selling_price' => 1200,
            'reason' => 'Appro direct',
            'user_id' => $manager->id,
        ]);
        $oldMovement->forceFill([
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ])->save();

        for ($i = 0; $i < 30; $i++) {
            $movement = StockMovement::create([
                'product_id' => $recentProduct->id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => 2,
                'quantity_before' => $i * 2,
                'quantity_after' => ($i + 1) * 2,
                'remaining_quantity' => 2,
                'purchase_price' => 100,
                'selling_price' => 150,
                'reason' => 'Appro direct',
                'user_id' => $manager->id,
            ]);
            $movement->forceFill([
                'created_at' => now()->subDays($i),
                'updated_at' => now()->subDays($i),
            ])->save();
        }

        $this->actingAs($manager)
            ->get(route('manager.stock.restocks'))
            ->assertOk()
            ->assertSee('Valeur')
            ->assertSee('6 000 FCFA')
            ->assertSee('Pain recent')
            ->assertDontSee('Pain ancien');

        $this->actingAs($manager)
            ->get(route('manager.stock.restocks', [
                'date_from' => now()->subDays(40)->toDateString(),
                'date_to' => now()->subDays(40)->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('999 FCFA')
            ->assertSee('Pain ancien')
            ->assertDontSee('Pain recent');
    }

    public function test_pdf_exports_render_for_super_admin_and_manager(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $superAdmin = User::where('email', 'nanguefyllias@gmail.com')->firstOrFail();
        $manager = User::where('email', 'bertholfyllias200@gmail.com')->firstOrFail();

        $superAdminRoutes = [
            'superadmin.users.export.pdf',
            'superadmin.sales.export.pdf',
            'superadmin.activity-logs.export.pdf',
        ];

        foreach ($superAdminRoutes as $route) {
            $this->actingAs($superAdmin)
                ->get(route($route))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }

        $managerRoutes = [
            'manager.sales.export.pdf',
            'manager.reports.sales.export.pdf',
        ];

        foreach ($managerRoutes as $route) {
            $this->actingAs($manager)
                ->get(route($route))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }

        $this->actingAs($manager)
            ->get(route('manager.stock.restocks.export.pdf', [
                'date_from' => '2026-04-25',
                'date_to' => '2026-04-25',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
