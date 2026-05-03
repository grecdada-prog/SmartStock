<?php

namespace Tests\Feature;

use App\Models\CashRegisterClosure;
use App\Models\CashBalanceAdjustment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerCashRegisterTest extends TestCase
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

    public function test_seller_can_close_cash_register_once_for_today(): void
    {
        $seller = $this->createSellerWithManager();
        $this->createSale($seller, 5000, 'cash', now());
        $this->createSale($seller, 2500, 'mobile_money', now());

        $response = $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $response->assertRedirect();

        $this->assertDatabaseHas('cash_register_closures', [
            'seller_id' => $seller->id,
            'amount' => 7500,
            'closed_by' => 'manual',
        ]);

        $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $this->assertSame(1, CashRegisterClosure::where('seller_id', $seller->id)->count());
    }

    public function test_closed_cash_register_resets_today_revenue_and_can_be_reopened(): void
    {
        $seller = $this->createSellerWithManager();
        $this->createSale($seller, 5000, 'cash', now());

        $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $response = $this->actingAs($seller)->get(route('seller.dashboard'));

        $response->assertOk()
            ->assertSee('0 FCFA')
            ->assertSee('Ouvrir la caisse')
            ->assertSee('Journee de vente terminee');

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));

        $response = $this->actingAs($seller)->get(route('seller.dashboard'));

        $response->assertOk()
            ->assertSee('Caisse rouverte')
            ->assertSee('Fermer la caisse');
    }

    public function test_daily_command_closes_current_day_cash_registers(): void
    {
        $seller = $this->createSellerWithManager();
        $this->createSale($seller, 7000, 'cash', now());

        $this->artisan('cash-registers:close-daily')->assertSuccessful();

        $this->assertDatabaseHas('cash_register_closures', [
            'seller_id' => $seller->id,
            'amount' => 7000,
            'closed_by' => 'automatic',
        ]);
    }

    public function test_manager_can_adjust_seller_cash_balance(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = User::findOrFail($seller->created_by);

        CashRegisterClosure::create([
            'seller_id' => $seller->id,
            'business_date' => today(),
            'amount' => 5000,
            'closed_by' => 'manual',
            'closed_at' => now(),
        ]);

        $this->actingAs($manager)->post(route('manager.sellers.cash-balance', $seller), [
            'type' => 'add',
            'amount' => 2000,
            'reason' => 'Depot',
        ])->assertRedirect();

        $this->actingAs($manager)->post(route('manager.sellers.cash-balance', $seller), [
            'type' => 'withdraw',
            'amount' => 1000,
            'reason' => 'Retrait',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'manager_id' => $manager->id,
            'type' => 'add',
            'amount' => 2000,
        ]);
        $this->assertSame(6000.0, app(\App\Services\CashRegisterService::class)->balanceForSeller($seller));
    }

    public function test_seller_dashboard_only_shows_requested_sections(): void
    {
        $seller = $this->createSellerWithManager();

        $response = $this->actingAs($seller)->get(route('seller.dashboard'));

        $response->assertOk()
            ->assertSee('Recette du jour')
            ->assertSee("Recette d'hier", false)
            ->assertSee('Solde Cash')
            ->assertSee('Fermer la caisse')
            ->assertDontSee('Produits Disponibles')
            ->assertDontSee('Mes Dernieres Ventes')
            ->assertDontSee('Consulter Produits')
            ->assertDontSee('Mes Statistiques');
    }

    private function createSellerWithManager(): User
    {
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('manager');

        $seller = User::factory()->create([
            'created_by' => $manager->id,
            'is_active' => true,
        ]);
        $seller->assignRole('seller');

        return $seller;
    }

    private function createSale(User $seller, int $total, string $paymentMethod, $createdAt): Sale
    {
        return Sale::create([
            'invoice_number' => 'INV-'.uniqid(),
            'seller_id' => $seller->id,
            'subtotal' => $total,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'amount_received' => $total,
            'change_given' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
