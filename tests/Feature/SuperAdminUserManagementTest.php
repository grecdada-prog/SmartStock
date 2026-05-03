<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\PreventDirectAccess;
use App\Http\Middleware\SingleSessionMiddleware;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Notifications\UserCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('', 200),
        ]);

        $this->withoutMiddleware([
            PreventDirectAccess::class,
            SingleSessionMiddleware::class,
            CheckInactivity::class,
            CheckUserActive::class,
        ]);
    }

    public function test_generic_super_admin_user_creation_keeps_account_active_when_checked(): void
    {
        Notification::fake();
        [$superAdmin] = $this->createUsers();

        $response = $this->actingAs($superAdmin)->post(route('superadmin.users.store'), [
            'name' => 'Gestionnaire actif',
            'email' => 'manager@example.com',
            'phone' => '',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'manager',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.users.index'));

        $user = User::where('email', 'manager@example.com')->firstOrFail();

        $this->assertTrue($user->is_active);
        $this->assertNull($user->phone);
        $this->assertTrue($user->hasRole('manager'));
        Notification::assertSentTo($user, UserCreatedNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_contains($mail->actionUrl, '/reset-password/')
                && ! str_contains($mail->render(), 'Password@123');
        });
    }

    public function test_super_admin_reset_password_sends_link_without_changing_password(): void
    {
        Notification::fake();
        [$superAdmin, $manager] = $this->createUsers();
        $originalPassword = $manager->password;

        $response = $this->actingAs($superAdmin)
            ->post(route('superadmin.users.reset-password', $manager));

        $response->assertRedirect();

        $this->assertSame($originalPassword, $manager->fresh()->password);
        Notification::assertSentTo($manager, PasswordResetNotification::class, function ($notification) use ($manager) {
            $mail = $notification->toMail($manager);

            return str_contains($mail->actionUrl, '/reset-password/');
        });
    }

    public function test_super_admin_can_update_user_password_from_edit_form(): void
    {
        Notification::fake();
        [$superAdmin, $manager] = $this->createUsers();

        $response = $this->actingAs($superAdmin)->put(route('superadmin.users.update', $manager), [
            'name' => $manager->name,
            'email' => $manager->email,
            'phone' => '',
            'role' => 'manager',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.users.index'));

        $this->assertTrue(Hash::check('NewPassword@123', $manager->fresh()->password));
        $this->assertNull($manager->fresh()->phone);
    }

    public function test_super_admin_cannot_change_own_role_or_deactivate_self_from_edit_form(): void
    {
        [$superAdmin] = $this->createUsers();

        $response = $this->actingAs($superAdmin)->from(route('superadmin.users.edit', $superAdmin))->put(route('superadmin.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'phone' => '',
            'role' => 'manager',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.users.edit', $superAdmin));
        $response->assertSessionHasErrors('role');

        $response = $this->actingAs($superAdmin)->from(route('superadmin.users.edit', $superAdmin))->put(route('superadmin.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'phone' => '',
            'role' => 'super_admin',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('superadmin.users.edit', $superAdmin));
        $response->assertSessionHasErrors('is_active');

        $superAdmin->refresh();

        $this->assertTrue($superAdmin->hasRole('super_admin'));
        $this->assertTrue($superAdmin->is_active);
    }

    public function test_super_admin_cannot_delete_or_toggle_own_account(): void
    {
        [$superAdmin] = $this->createUsers();

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.users.destroy', $superAdmin))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.users.toggle-status', $superAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
            'is_active' => true,
        ]);
    }

    private function createUsers(): array
    {
        Role::findOrCreate('super_admin');
        Role::findOrCreate('manager');
        Role::findOrCreate('seller');

        $superAdmin = User::factory()->create([
            'is_active' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        $manager = User::factory()->create([
            'is_active' => true,
            'created_by' => $superAdmin->id,
        ]);
        $manager->assignRole('manager');

        return [$superAdmin, $manager];
    }
}
