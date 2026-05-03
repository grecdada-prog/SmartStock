<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\PreventDirectAccess;
use App\Http\Middleware\SingleSessionMiddleware;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
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
        Excel::fake();

        $superAdmin = User::where('email', 'nanguefyllias@gmail.com')->firstOrFail();

        $this->actingAs($superAdmin)->get(route('superadmin.users.export.excel'))->assertOk();
        Excel::assertDownloaded('utilisateurs_2026-04-25_10-30-00.xlsx');

        $this->actingAs($superAdmin)->get(route('superadmin.sales.export.excel'))->assertOk();
        Excel::assertDownloaded('ventes_2026-04-25_10-30-00.xlsx');

        $this->actingAs($superAdmin)->get(route('superadmin.activity-logs.export.excel'))->assertOk();
        Excel::assertDownloaded('logs_activite_2026-04-25_10-30-00.xlsx');
    }

    public function test_manager_excel_exports_are_downloaded(): void
    {
        $this->seed(DatabaseSeeder::class);
        Excel::fake();

        $manager = User::where('email', 'manager@smartstock.test')->firstOrFail();

        $this->actingAs($manager)->get(route('manager.sales.export.excel'))->assertOk();
        Excel::assertDownloaded('ventes_manager_2026-04-25_10-30-00.xlsx');
    }

    public function test_pdf_exports_render_for_super_admin_and_manager(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superAdmin = User::where('email', 'nanguefyllias@gmail.com')->firstOrFail();
        $manager = User::where('email', 'manager@smartstock.test')->firstOrFail();

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
    }
}
