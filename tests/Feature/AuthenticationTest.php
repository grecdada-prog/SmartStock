<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('manager');
        $user->assignRole('manager');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('manager.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_seller_is_redirected_from_email_role_without_selecting_role(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('seller');
        $user->assignRole('seller');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('seller.dashboard', absolute: false));
    }

    public function test_remember_me_sets_the_recaller_cookie(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('manager');
        $user->assignRole('manager');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertCookie(Auth::guard()->getRecallerName());
    }

    public function test_users_can_complete_custom_two_factor_challenge(): void
    {
        Role::findOrCreate('manager');

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->create([
            'google2fa_enabled' => true,
            'google2fa_secret' => $secret,
        ]);
        $user->assignRole('manager');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertRedirect(route('2fa.verify', absolute: false))
            ->assertSessionHas('2fa:user:id', $user->id);
        $this->assertGuest();

        $this->get(route('2fa.verify'))->assertOk();

        $response = $this->post(route('2fa.verify.post'), [
            'one_time_password' => $google2fa->getCurrentOtp($secret),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('manager.dashboard', absolute: false));
    }

    public function test_seller_can_complete_custom_two_factor_challenge(): void
    {
        Role::findOrCreate('seller');

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->create([
            'google2fa_enabled' => true,
            'google2fa_secret' => $secret,
        ]);
        $user->assignRole('seller');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ])->assertRedirect(route('2fa.verify', absolute: false));

        $response = $this->post(route('2fa.verify.post'), [
            'one_time_password' => str_pad((string) $google2fa->getCurrentOtp($secret), 6, '0', STR_PAD_LEFT),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('seller.dashboard', absolute: false));
    }

    public function test_custom_two_factor_challenge_allows_post_when_csrf_middleware_is_active(): void
    {
        $this->withMiddleware(ValidateCsrfToken::class);

        Role::findOrCreate('manager');

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->create([
            'google2fa_enabled' => true,
            'google2fa_secret' => $secret,
        ]);
        $user->assignRole('manager');

        $this->withSession(['2fa:user:id' => $user->id])
            ->post(route('2fa.verify.post'), [
                'one_time_password' => $google2fa->getCurrentOtp($secret),
            ])
            ->assertRedirect(route('manager.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
