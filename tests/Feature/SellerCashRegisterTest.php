<?php

namespace Tests\Feature;

use App\Models\CashRegisterClosure;
use App\Models\CashBalanceAdjustment;
use App\Models\Sale;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
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
            ->assertSee('2 vente(s)');

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

    public function test_closed_cash_register_keeps_today_revenue_visible_and_can_be_reopened(): void
    {
        $seller = $this->createSellerWithManager();
        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 5000, 'cash', now());

        $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $response = $this->actingAs($seller)->get(route('seller.dashboard'));

        $response->assertOk()
            ->assertSee("showToday ? '5 000 FCFA' : '******'", false)
            ->assertSee('Ouvrir la caisse')
            ->assertSee('Caisse Cash');

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

    public function test_midnight_schedule_closes_previous_day_and_seller_can_open_next_morning(): void
    {
        $seller = $this->createSellerWithManager();

        $this->travelTo(Carbon::parse('2026-05-24 09:00:00', 'Africa/Douala'));
        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 7000, 'cash', now());

        $this->travelTo(Carbon::parse('2026-05-25 00:00:00', 'Africa/Douala'));
        $this->artisan('cash-registers:close-daily --yesterday')->assertSuccessful();

        $closure = CashRegisterClosure::where('seller_id', $seller->id)
            ->whereDate('business_date', '2026-05-24')
            ->firstOrFail();

        $this->assertSame(7000.0, (float) $closure->amount);
        $this->assertSame('automatic', $closure->closed_by);
        $this->assertNull($closure->opened_at);

        $this->travelTo(Carbon::parse('2026-05-25 08:00:00', 'Africa/Douala'));

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('Ouvrir la caisse')
            ->assertSee('Caisse non ouverte');

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('Caisse ouverte')
            ->assertSee('Fermer la caisse');
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

        $this->assertSame(5000.0, app(\App\Services\CashRegisterService::class)->balanceForSeller($seller));

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

        $this->assertSame(5000.0, $cashRegisterService->balanceForSeller($seller));
        $this->assertSame(2000.0, $cashRegisterService->orangeMoneyBalanceForSeller($seller));
        $this->assertSame(3000.0, $cashRegisterService->mtnMomoBalanceForSeller($seller));
        $this->assertSame(5000.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

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

    public function test_current_day_revenue_includes_mobile_payments_and_closure_separates_balances(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = User::findOrFail($seller->created_by);
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('super_admin');
        $cashRegisterService = app(\App\Services\CashRegisterService::class);

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 5000, 'cash', now());
        $this->createSale($seller, 2000, 'card', now());
        $this->createSale($seller, 3000, 'mobile_money', now());

        $this->assertSame(10000.0, $cashRegisterService->currentDayRevenueForSeller($seller));
        $this->assertSame(5000.0, $cashRegisterService->currentDayCashRevenueForSeller($seller));
        $this->assertSame(5000.0, $cashRegisterService->balanceForSeller($seller));
        $this->assertSame(5000.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee("showToday ? '10 000 FCFA' : '******'", false);

        $this->actingAs($manager)
            ->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee("showToday ? '10 000 FCFA' : '******'", false);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('10 000 FCFA');

        $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $this->assertSame(10000.0, $cashRegisterService->currentDayRevenueForSeller($seller));
        $this->assertSame(5000.0, $cashRegisterService->currentDayCashRevenueForSeller($seller));
        $this->assertSame(5000.0, $cashRegisterService->balanceForSeller($seller));
        $this->assertSame(5000.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

        $this->assertDatabaseHas('cash_register_closures', [
            'seller_id' => $seller->id,
            'amount' => 5000,
        ]);
    }

    public function test_balances_remain_visible_after_reopening_cash_register(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = User::findOrFail($seller->created_by);
        $cashRegisterService = app(\App\Services\CashRegisterService::class);

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));
        $this->createSale($seller, 5000, 'cash', now());
        $this->createSale($seller, 3000, 'mobile_money', now());
        $this->actingAs($seller)->post(route('seller.dashboard.close-cash-register'));

        $this->assertSame(5000.0, $cashRegisterService->balanceForSeller($seller));
        $this->assertSame(3000.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));

        $this->assertSame(5000.0, $cashRegisterService->balanceForSeller($seller));
        $this->assertSame(3000.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

        $this->createSale($seller, 2000, 'cash', now());
        $this->createSale($seller, 1000, 'mobile_money', now());

        $this->assertSame(7000.0, $cashRegisterService->balanceForSeller($seller));
        $this->assertSame(4000.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee("showCash ? '7 000 FCFA' : '******'", false)
            ->assertSee("showMobile ? '4 000 FCFA' : '******'", false);

        $this->actingAs($manager)
            ->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee("showCash ? '7 000 FCFA' : '******'", false)
            ->assertSee("showMobile ? '4 000 FCFA' : '******'", false);

        $this->actingAs($manager)
            ->get(route('manager.sellers.index'))
            ->assertOk()
            ->assertSee('Total Caisse Cash')
            ->assertSee('Total Caisse MOMO/OM')
            ->assertSee('7 000 FCFA')
            ->assertSee('4 000 FCFA');
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
            'balance_type' => 'cash',
            'amount' => 2000,
            'reason' => 'Depot',
        ])->assertRedirect();

        $this->actingAs($manager)->post(route('manager.sellers.cash-balance', $seller), [
            'type' => 'withdraw',
            'balance_type' => 'cash',
            'amount' => 1000,
            'reason' => 'Retrait',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'manager_id' => $manager->id,
            'type' => 'add',
            'balance_type' => 'cash',
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
            'balance_type' => 'cash',
            'amount' => 3000,
            'reason' => 'Versement banque',
        ])->assertRedirect();

        $this->actingAs($manager)->post(route('manager.sellers.cash-balance', $seller), [
            'type' => 'withdraw',
            'balance_type' => 'cash',
            'amount' => 2500,
            'reason' => 'Deuxieme retrait',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'manager_id' => $manager->id,
            'type' => 'withdraw',
            'balance_type' => 'cash',
            'amount' => 3000,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'cash_balance_withdrawn',
            'model' => 'CashBalanceAdjustment',
        ]);
    }

    public function test_manager_can_adjust_seller_mobile_money_balance(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = User::findOrFail($seller->created_by);
        $cashRegisterService = app(\App\Services\CashRegisterService::class);

        $this->createSale($seller, 4000, 'mobile_money', now()->subMinute());

        CashRegisterClosure::create([
            'seller_id' => $seller->id,
            'business_date' => today(),
            'amount' => 0,
            'closed_by' => 'manual',
            'closed_at' => now(),
        ]);

        $this->assertSame(4000.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));

        $this->actingAs($manager)->post(route('manager.sellers.cash-balance', $seller), [
            'type' => 'add',
            'balance_type' => 'mobile_money',
            'amount' => 1500,
            'reason' => 'Correction mobile',
        ])->assertRedirect();

        $this->actingAs($manager)->post(route('manager.sellers.cash-balance', $seller), [
            'type' => 'withdraw',
            'balance_type' => 'mobile_money',
            'amount' => 1000,
            'reason' => 'Versement mobile',
        ])->assertRedirect();

        $this->assertSame(4500.0, $cashRegisterService->mobileMoneyBalanceForSeller($seller));
        $this->assertSame(0.0, $cashRegisterService->balanceForSeller($seller));

        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'manager_id' => $manager->id,
            'type' => 'withdraw',
            'balance_type' => 'mobile_money',
            'amount' => 1000,
        ]);
    }

    public function test_seller_cash_service_records_separate_adjustments_without_creating_sales(): void
    {
        $seller = $this->createSellerWithManager();
        $salesBefore = Sale::count();

        $this->createSale($seller, 5000, 'mobile_money', now()->subMinute());

        $this->actingAs($seller)->post(route('seller.services.store'), [
            'operation' => 'depot',
            'amount' => 2000,
            'reason' => 'Client depot',
        ])->assertRedirect(route('seller.services.index'));

        $this->assertSame($salesBefore + 1, Sale::count());

        $adjustments = CashBalanceAdjustment::where('seller_id', $seller->id)
            ->where('source', 'service')
            ->whereNotNull('reference_id')
            ->get();

        $this->assertCount(2, $adjustments);
        $this->assertCount(1, $adjustments->pluck('reference_id')->unique());
        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'type' => 'add',
            'balance_type' => CashRegisterService::CASH_BALANCE_TYPE,
            'amount' => 2000,
            'source' => 'service',
        ]);
        $this->assertDatabaseHas('cash_balance_adjustments', [
            'seller_id' => $seller->id,
            'type' => 'withdraw',
            'balance_type' => CashRegisterService::MOBILE_MONEY_BALANCE_TYPE,
            'amount' => 2000,
            'source' => 'service',
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

    public function test_manager_closure_shows_seller_notice_until_acknowledged(): void
    {
        $seller = $this->createSellerWithManager();
        $manager = User::findOrFail($seller->created_by);

        $this->actingAs($seller)->post(route('seller.dashboard.open-cash-register'));

        $this->actingAs($manager)
            ->post(route('manager.sellers.cash-register.close', $seller))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(Cache::get('seller_cash_register_closed_notice:'.$seller->id));

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('Caisse cloturee')
            ->assertSee('Ton gerant a cloture ta caisse');

        $this->actingAs($seller)
            ->post(route('seller.dashboard.manager-closure-notice.ack'))
            ->assertNoContent();

        $this->assertNull(Cache::get('seller_cash_register_closed_notice:'.$seller->id));
    }

    public function test_seller_dashboard_only_shows_requested_sections(): void
    {
        $seller = $this->createSellerWithManager();

        $response = $this->actingAs($seller)->get(route('seller.dashboard'));

        $response->assertOk()
            ->assertSee('Recette du jour')
            ->assertDontSee("Recette d'hier", false)
            ->assertSee('Caisse Cash')
            ->assertSee('Caisse MOMO/OM')
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
        Role::findOrCreate('super_admin');

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
        $sale = Sale::create([
            'invoice_number' => 'INV-'.uniqid(),
            'seller_id' => $seller->id,
            'subtotal' => $total,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'amount_received' => $total,
            'change_given' => 0,
        ]);

        $sale->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $sale;
    }
}
