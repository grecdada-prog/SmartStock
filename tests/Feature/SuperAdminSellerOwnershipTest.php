<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ActivityLog;
use App\Notifications\UserCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminSellerOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('', 200),
        ]);

        $this->withoutMiddleware([
            \App\Http\Middleware\PreventDirectAccess::class,
            \App\Http\Middleware\SingleSessionMiddleware::class,
            \App\Http\Middleware\CheckInactivity::class,
            \App\Http\Middleware\CheckUserActive::class,
        ]);
    }

    public function test_super_admin_must_assign_seller_to_manager(): void
    {
        Notification::fake();
        [$superAdmin] = $this->createUsers();

        $response = $this->actingAs($superAdmin)->post(route('superadmin.sellers.store'), [
            'name' => 'Vendeur sans manager',
            'email' => 'seller@example.com',
            'phone' => '690123456',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('manager_id');
        $this->assertDatabaseMissing('users', ['email' => 'seller@example.com']);
    }

    public function test_super_admin_created_seller_belongs_to_selected_manager(): void
    {
        Notification::fake();
        [$superAdmin, $manager] = $this->createUsers();

        $response = $this->actingAs($superAdmin)->post(route('superadmin.sellers.store'), [
            'name' => 'Vendeur rattache',
            'email' => 'seller@example.com',
            'phone' => '690123456',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'manager_id' => $manager->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.sellers.index'));

        $seller = User::where('email', 'seller@example.com')->firstOrFail();

        $this->assertTrue($seller->hasRole('seller'));
        $this->assertSame($manager->id, $seller->created_by);
        Notification::assertSentTo($seller, UserCreatedNotification::class);
    }

    public function test_super_admin_can_reassign_seller_with_audit_reason(): void
    {
        Notification::fake();
        [$superAdmin, $manager] = $this->createUsers();
        $newManager = User::factory()->create([
            'created_by' => $superAdmin->id,
            'is_active' => true,
        ]);
        $newManager->assignRole('manager');

        $seller = User::factory()->create([
            'created_by' => $manager->id,
            'is_active' => true,
        ]);
        $seller->assignRole('seller');

        $response = $this->actingAs($superAdmin)->post(route('superadmin.sellers.reassign-manager', $seller), [
            'manager_id' => $newManager->id,
            'reason' => 'Changement de secteur commercial.',
        ]);

        $response->assertRedirect(route('superadmin.sellers.edit', $seller));

        $seller->refresh();

        $this->assertSame($newManager->id, $seller->created_by);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'seller_reassigned',
            'model' => 'User',
            'model_id' => $seller->id,
        ]);

        $log = ActivityLog::where('action', 'seller_reassigned')->latest()->first();

        $this->assertSame($manager->id, data_get($log->properties, 'previous_manager_id'));
        $this->assertSame($newManager->id, data_get($log->properties, 'new_manager_id'));
        $this->assertSame('Changement de secteur commercial.', data_get($log->properties, 'reason'));
    }

    private function createUsers(): array
    {
        Role::findOrCreate('super_admin');
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $manager = User::factory()->create([
            'created_by' => $superAdmin->id,
        ]);
        $manager->assignRole('manager');

        return [$superAdmin, $manager];
    }
}
