<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CashRegisterClosure;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminCashRegisterControlTest extends TestCase
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

    public function test_super_admin_can_force_close_a_seller_cash_register_with_reason(): void
    {
        [$superAdmin, $seller] = $this->createSuperAdminAndSeller();

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));

        Sale::create([
            'invoice_number' => 'INV-TST-0001',
            'seller_id' => $seller->id,
            'subtotal' => 8200,
            'total' => 8200,
            'payment_method' => 'cash',
            'amount_received' => 10000,
            'change_given' => 1800,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('superadmin.sellers.cash-register.close', $seller), [
            'reason' => 'Controle de fin de journee par le superadmin.',
        ]);

        $response->assertRedirect(route('superadmin.sellers.edit', $seller));

        $this->assertDatabaseHas('cash_register_closures', [
            'seller_id' => $seller->id,
            'closed_by' => 'super_admin',
            'amount' => 8200,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'seller_cash_register_force_closed',
            'model' => 'CashRegisterClosure',
        ]);

        $log = ActivityLog::where('action', 'seller_cash_register_force_closed')->latest()->first();

        $this->assertSame($seller->id, data_get($log->properties, 'seller_id'));
        $this->assertSame('Controle de fin de journee par le superadmin.', data_get($log->properties, 'reason'));
    }

    public function test_super_admin_cannot_open_a_seller_cash_register(): void
    {
        $this->assertFalse(Route::has('superadmin.sellers.cash-register.open'));
    }

    public function test_seller_opening_is_traced_with_seller_actor(): void
    {
        [, $seller] = $this->createSuperAdminAndSeller();

        $closure = CashRegisterClosure::create([
            'seller_id' => $seller->id,
            'business_date' => today(),
            'amount' => 5500,
            'closed_by' => 'manual',
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));

        $response->assertRedirect();

        $closure->refresh();

        $this->assertNotNull($closure->opened_at);
        $this->assertDatabaseHas('cash_register_closures', [
            'id' => $closure->id,
            'opened_by' => 'seller',
            'opened_by_user_id' => $seller->id,
        ]);
    }

    private function createSuperAdminAndSeller(): array
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

        return [$superAdmin, $seller];
    }
}
