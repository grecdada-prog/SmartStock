<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\PreventDirectAccess;
use App\Http\Middleware\SingleSessionMiddleware;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePageSmokeTest extends TestCase
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

    public function test_super_admin_main_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $superAdmin = User::where('email', 'nanguefyllias@gmail.com')->firstOrFail();

        $routes = [
            'superadmin.dashboard',
            'superadmin.statistics',
            'superadmin.users.index',
            'superadmin.users.create',
            'superadmin.managers.index',
            'superadmin.managers.create',
            'superadmin.sellers.index',
            'superadmin.sellers.create',
            'superadmin.products',
            'superadmin.sales',
            'superadmin.anomalies',
            'superadmin.activity-logs',
            'superadmin.sessions.active',
            'account.profile.show',
        ];

        foreach ($routes as $route) {
            $this->actingAs($superAdmin)
                ->get(route($route))
                ->assertOk();
        }

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertSee('Supervision des gérants')
            ->assertSee('Alertes superadmin')
            ->assertSee('Pouvoirs rapides');

        $this->actingAs($superAdmin)
            ->get(route('superadmin.anomalies'))
            ->assertSee('Centre des anomalies')
            ->assertSee('Severite')
            ->assertSee('Action conseillee');
    }

    public function test_manager_main_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $manager = User::where('email', 'bertholfyllias200@gmail.com')->firstOrFail();
        $seller = User::where('email', 'grecdada@gmail.com')->firstOrFail();
        $category = Category::where('created_by', $manager->id)->firstOrFail();
        $product = Product::where('created_by', $manager->id)->firstOrFail();

        $routes = [
            ['manager.dashboard'],
            ['manager.sellers.index'],
            ['manager.sellers.create'],
            ['manager.sellers.online'],
            ['manager.sellers.show', $seller],
            ['manager.sellers.edit', $seller],
            ['manager.categories.index'],
            ['manager.categories.create'],
            ['manager.categories.show', $category],
            ['manager.categories.edit', $category],
            ['manager.products.index'],
            ['manager.products.create'],
            ['manager.products.show', $product],
            ['manager.products.edit', $product],
            ['manager.products.low-stock'],
            ['manager.stock.index'],
            ['manager.stock.low-stock'],
            ['manager.stock.restock'],
            ['manager.stock.movements'],
            ['manager.sales'],
            ['manager.reports.index'],
            ['manager.reports.sales'],
            ['manager.reports.activity'],
            ['manager.reports.stock'],
            ['account.profile.show'],
        ];

        foreach ($routes as $route) {
            $name = array_shift($route);

            $this->actingAs($manager)
                ->get(route($name, $route))
                ->assertOk();
        }

        $this->actingAs($manager)
            ->get(route('manager.products.create'))
            ->assertSee('Créer le produit')
            ->assertSee("Seuil d'alerte", false)
            ->assertDontSee('marge estimee');

        $this->actingAs($manager)
            ->get(route('manager.stock.index'))
            ->assertDontSee('Actualiser')
            ->assertDontSee('Ajustement')
            ->assertDontSee('Auto-refresh');

        $this->actingAs($manager)
            ->get(route('manager.sales'))
            ->assertSee('Ventes filtrees')
            ->assertSee('Recette du jour')
            ->assertSee("Recette d'hier", false)
            ->assertSee('Solde Cash')
            ->assertDontSee('Ce mois')
            ->assertDontSee('CA total');
    }

    public function test_seller_main_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $seller = User::where('email', 'grecdada@gmail.com')->firstOrFail();
        $product = Product::where('created_by', $seller->created_by)->active()->firstOrFail();
        $sale = Sale::where('seller_id', $seller->id)->firstOrFail();

        $routes = [
            ['seller.dashboard'],
            ['seller.pos.index'],
            ['seller.sales.history'],
            ['seller.sales.show', $sale],
            ['seller.pos.receipt', $sale],
            ['seller.products'],
            ['seller.products.show', $product],
            ['seller.my-stats'],
            ['account.profile.show'],
        ];

        foreach ($routes as $route) {
            $name = array_shift($route);

            $this->actingAs($seller)
                ->get(route($name, $route))
                ->assertOk();
        }

        $this->actingAs($seller)
            ->get(route('seller.pos.index'))
            ->assertSee('Montant recu insuffisant')
            ->assertSee('Indisponible');
    }
}
