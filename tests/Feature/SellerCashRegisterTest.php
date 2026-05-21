<?php

namespace Tests\Feature;

use App\Models\CashRegisterClosure;
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
        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 5000, 'cash', now());
        $this->createSale($seller, 2500, 'mobile_money', now());

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee("showToday ? '7 500 FCFA' : '******'", false)
            ->assertSee('2 vente(s), toutes methodes');

        $response = $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $response->assertRedirect();

        $this->assertDatabaseHas('cash_register_closures', [
            'seller_id' => $seller->id,
            'amount' => 5000,
            'closed_by' => 'manual',
        ]);

        $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $this->assertSame(1, CashRegisterClosure::where('seller_id', $seller->id)->count());
    }

    public function test_repeated_close_attempts_do_not_trigger_normal_429_flow(): void
    {
        $seller = $this->createSellerWithManager();
        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 5000, 'cash', now());

        $this->actingAs($seller)
            ->post(route('seller.dashboard.close-cash-register'))
            ->assertRedirect();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($seller)
                ->post(route('seller.dashboard.close-cash-register'))
                ->assertRedirect()
                ->assertSessionHas('warning');
        }

        $this->assertSame(1, CashRegisterClosure::where('seller_id', $seller->id)->count());
    }

    public function test_closed_cash_register_resets_today_revenue_and_can_be_reopened(): void
    {
        $seller = $this->createSellerWithManager();
        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
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
            ->assertSee('Caisse ouverte')
            ->assertSee('Fermer la caisse');
    }

    public function test_daily_command_closes_current_day_cash_registers(): void
    {
        $seller = $this->createSellerWithManager();
        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 7000, 'cash', now());

        $this->artisan('cash-registers:close-daily')->assertSuccessful();

        $this->assertDatabaseHas('cash_register_closures', [
            'seller_id' => $seller->id,
            'amount' => 7000,
            'closed_by' => 'automatic',
        ]);
    }

    public function test_daily_command_does_not_create_closure_for_unopened_cash_registers(): void
    {
        $seller = $this->createSellerWithManager();

        $this->artisan('cash-registers:close-daily')->assertSuccessful();

        $this->assertDatabaseMissing('cash_register_closures', [
            'seller_id' => $seller->id,
        ]);
    }

    public function test_cash_register_balance_ignores_non_cash_payments(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = User::findOrFail($seller->created_by);
        Role::findOrCreate('super_admin');

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 5000, 'cash', now());
        $this->createSale($seller, 2500, 'mobile_money', now());
        $this->createSale($seller, 1000, 'card', now());

        $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $this->assertDatabaseHas('cash_register_closures', [
            'seller_id' => $seller->id,
            'amount' => 5000,
        ]);
        $this->assertSame(5000.0, app(\App\Services\CashRegisterService::class)->balanceForSeller($seller));

        $this->actingAs($manager)
            ->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee("showCash ? '5 000 FCFA' : '******'", false);
    }

    public function test_mobile_payment_balances_are_tracked_separately_from_cash(): void
    {
        $seller = $this->createSellerWithManager();
        $cashRegisterService = app(\App\Services\CashRegisterService::class);

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 5000, 'cash', now());
        $this->createSale($seller, 2000, 'card', now());
        $this->createSale($seller, 3000, 'mobile_money', now());

        $this->assertSame(0.0, $cashRegisterService->orangeMoneyBalanceForSeller($seller));
        $this->assertSame(0.0, $cashRegisterService->mtnMomoBalanceForSeller($seller));
        $this->assertSame(0.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

        $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $this->assertSame(5000.0, $cashRegisterService->balanceForSeller($seller));
        $this->assertSame(2000.0, $cashRegisterService->orangeMoneyBalanceForSeller($seller));
        $this->assertSame(3000.0, $cashRegisterService->mtnMomoBalanceForSeller($seller));
        $this->assertSame(5000.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee("showCash ? '5 000 FCFA' : '******'", false)
            ->assertSee("showMobile ? '5 000 FCFA' : '******'", false);
    }

    public function test_cash_register_balance_recalculates_legacy_closures_from_cash_sales(): void
    {
        $seller = $this->createSellerWithManager();

        $this->createSale($seller, 5000, 'cash', now()->subHour());
        $this->createSale($seller, 2500, 'mobile_money', now()->subHour());

        CashRegisterClosure::create([
            'seller_id' => $seller->id,
            'business_date' => today(),
            'amount' => 7500,
            'closed_by' => 'manual',
            'closed_at' => now(),
        ]);

        $this->assertSame(5000.0, app(\App\Services\CashRegisterService::class)->balanceForSeller($seller));
    }

    public function test_manager_can_adjust_seller_cash_balance(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = User::findOrFail($seller->created_by);
        $this->createSale($seller, 5000, 'cash', now()->subMinute());

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

    public function test_manager_withdrawal_uses_seller_cash_balance(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = User::findOrFail($seller->created_by);
        $this->createSale($seller, 5000, 'cash', now()->subMinute());

        CashRegisterClosure::create([
            'seller_id' => $seller->id,
            'business_date' => today(),
            'amount' => 5000,
            'closed_by' => 'manual',
            'closed_at' => now(),
        ]);

        $this->actingAs($manager)->post(route('manager.sellers.cash-balance', $seller), [
            'type' => 'withdraw',
            'amount' => 3000,
            'reason' => 'Versement banque',
        ])->assertRedirect();

        $this->actingAs($manager)->post(route('manager.sellers.cash-balance', $seller), [
            'type' => 'withdraw',
            'amount' => 2500,
            'reason' => 'Deuxieme retrait',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'manager_id' => $manager->id,
            'type' => 'withdraw',
            'amount' => 3000,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'cash_balance_withdrawn',
            'model' => 'CashBalanceAdjustment',
        ]);
    }

    public function test_manager_does_not_see_close_cash_register_action_when_cash_register_is_closed(): void
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

        $this->actingAs($manager)
            ->get(route('manager.sellers.index'))
            ->assertOk()
            ->assertDontSee(route('manager.sellers.cash-register.close', $seller), false);
    }

    public function test_seller_dashboard_only_shows_requested_sections(): void
    {
        $seller = $this->createSellerWithManager();

        $response = $this->actingAs($seller)->get(route('seller.dashboard'));

        $response->assertOk()
            ->assertSee('Recette du jour')
            ->assertSee("Recette d'hier", false)
            ->assertSee('Solde Cash')
            ->assertSee('Ouvrir la caisse')
            ->assertDontSee('Fermer la caisse')
            ->assertDontSee('Produits Disponibles')
            ->assertDontSee('Mes Dernieres Ventes')
            ->assertDontSee('Consulter Produits')
            ->assertDontSee('Mes Statistiques');
    }

    public function test_seller_must_open_cash_register_before_closing_it(): void
    {
        $seller = $this->createSellerWithManager();

        $this->actingAs($seller)
            ->post(route('seller.dashboard.close-cash-register'))
            ->assertSessionHas('warning');

        $this->assertDatabaseMissing('cash_register_closures', [
            'seller_id' => $seller->id,
        ]);
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
