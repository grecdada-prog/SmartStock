<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\PreventDirectAccess;
use App\Http\Middleware\SingleSessionMiddleware;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
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
            ValidateCsrfToken::class,
        ]);
    }

    public function test_profile_route_names_do_not_conflict_with_jetstream(): void
    {
        $namedRoutes = collect(Route::getRoutes()->getRoutesByName());

        $this->assertTrue(Route::has('profile.show'));
        $this->assertTrue(Route::has('account.profile.show'));
        $this->assertTrue(Route::has('account.profile.update'));
        $this->assertTrue(Route::has('account.profile.password.update'));
        $this->assertSame('/user/profile', parse_url(route('profile.show'), PHP_URL_PATH));
        $this->assertSame('/profile', parse_url(route('account.profile.show'), PHP_URL_PATH));
        $this->assertSame(1, $namedRoutes->keys()->filter(fn ($name) => $name === 'profile.show')->count());
    }

    public function test_two_factor_verify_post_route_is_named(): void
    {
        $this->assertTrue(Route::has('2fa.verify.post'));
        $this->assertSame('/2fa/verify', parse_url(route('2fa.verify.post'), PHP_URL_PATH));
    }

    public function test_super_admin_force_logout_route_is_named(): void
    {
        $this->assertTrue(Route::has('superadmin.users.force-logout'));
    }

    public function test_seller_token_recent_check_route_is_registered_once(): void
    {
        $namedRoutes = collect(Route::getRoutes()->getRoutesByName());
        $matchingUris = collect(Route::getRoutes())
            ->filter(fn ($route) => in_array('seller/tokens/check-recent', $route->methods(), true) || $route->uri() === 'seller/tokens/check-recent');

        $this->assertTrue(Route::has('seller.tokens.check-recent'));
        $this->assertSame(1, $namedRoutes->keys()->filter(fn ($name) => $name === 'seller.tokens.check-recent')->count());
        $this->assertSame(1, $matchingUris->count());
    }

    public function test_seller_cannot_update_own_password_from_custom_profile(): void
    {
        Role::findOrCreate('seller');

        $seller = User::factory()->create([
            'is_active' => true,
        ]);
        $seller->assignRole('seller');

        $response = $this->actingAs($seller)->put(route('account.profile.password.update'), [
            'current_password' => 'password',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $response->assertForbidden();
    }
}
