<?php

namespace Tests\Feature;

use App\Models\CashRegisterClosure;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminAnomaliesTest extends TestCase
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

    public function test_super_admin_anomalies_page_lists_detected_issues(): void
    {
        [$superAdmin, $manager, $seller] = $this->createUsers();
        $category = Category::create([
            'name' => 'Categorie test',
            'description' => 'Categorie de test',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        Product::create([
            'name' => 'Produit critique',
            'sku' => 'SKU-ALERTE',
            'category_id' => $category->id,
            'purchase_price' => 1000,
            'selling_price' => 1500,
            'quantity' => 0,
            'alert_quantity' => 3,
            'unit' => 'piece',
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        CashRegisterClosure::create([
            'seller_id' => $seller->id,
            'business_date' => today()->subDay(),
            'amount' => 4500,
            'closed_by' => 'manual',
            'closed_at' => now()->subHours(10),
        ]);

        $response = $this->actingAs($superAdmin)->get(route('superadmin.anomalies'));

        $response->assertOk()
            ->assertSee('Centre des anomalies')
            ->assertSee('Stock faible critique')
            ->assertSee('Caisse fermee depuis trop longtemps');
    }

    private function createUsers(): array
    {
        Role::findOrCreate('super_admin');
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('super_admin');

        $manager = User::factory()->create([
            'created_by' => $superAdmin->id,
            'is_active' => true,
        ]);
        $manager->assignRole('manager');

        $seller = User::factory()->create([
            'created_by' => $manager->id,
            'is_active' => true,
        ]);
        $seller->assignRole('seller');

        return [$superAdmin, $manager, $seller];
    }
}
