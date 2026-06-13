<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInactivity;
use App\Models\ActiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckInactivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_logged_out_after_fifteen_minutes_of_inactivity(): void
    {
        $user = User::factory()->create([
            'last_activity' => now()->subMinutes(15),
        ]);
        Role::findOrCreate('seller');
        $user->assignRole('seller');

        Auth::login($user);

        $request = Request::create('/manager/dashboard', 'GET');
        $request->setLaravelSession(new Store('array', new ArraySessionHandler(120)));

        $response = (new CheckInactivity())->handle($request, fn () => response('ok'));

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertFalse(Auth::check());
    }

    public function test_seller_is_kept_active_before_fifteen_minutes(): void
    {
        $user = User::factory()->create([
            'last_activity' => now()->subMinutes(14),
        ]);
        Role::findOrCreate('seller');
        $user->assignRole('seller');

        Auth::login($user);

        $request = Request::create('/seller/dashboard', 'GET');
        $request->setLaravelSession(new Store('array', new ArraySessionHandler(120)));

        $response = (new CheckInactivity())->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue(Auth::check());
    }

    public function test_active_session_is_kept_when_user_is_still_active(): void
    {
        $user = User::factory()->create([
            'last_activity' => now()->subMinutes(14),
        ]);
        Role::findOrCreate('manager');
        $user->assignRole('manager');

        Auth::login($user);

        $request = Request::create('/manager/dashboard', 'GET');
        $session = new Store('array', new ArraySessionHandler(120));
        $request->setLaravelSession($session);

        ActiveSession::create([
            'user_id' => $user->id,
            'session_id' => $session->getId(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Tests',
            'last_activity' => now()->subMinutes(9),
        ]);

        $response = (new CheckInactivity())->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue(Auth::check());
        $this->assertTrue(ActiveSession::first()->last_activity->greaterThan(now()->subMinute()));
    }

    public function test_expired_json_request_gets_session_expired_response(): void
    {
        $user = User::factory()->create([
            'last_activity' => now()->subMinutes(15),
        ]);
        Role::findOrCreate('seller');
        $user->assignRole('seller');

        Auth::login($user);

        $request = Request::create('/seller/pos/sale', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        $request->setLaravelSession(new Store('array', new ArraySessionHandler(120)));

        $response = (new CheckInactivity())->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(419, $response->getStatusCode());
        $this->assertStringContainsString('session a expire', $response->getContent());
        $this->assertFalse(Auth::check());
    }

    public function test_heartbeat_request_is_allowed_before_timeout(): void
    {
        $user = User::factory()->create([
            'last_activity' => now()->subMinutes(4),
        ]);
        Role::findOrCreate('seller');
        $user->assignRole('seller');

        Auth::login($user);

        $request = Request::create('/session/heartbeat', 'POST');
        $session = new Store('array', new ArraySessionHandler(120));
        $request->setLaravelSession($session);

        ActiveSession::create([
            'user_id' => $user->id,
            'session_id' => $session->getId(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Tests',
            'last_activity' => now()->subMinutes(4),
        ]);

        $response = (new CheckInactivity())->handle($request, fn () => response()->noContent());

        $this->assertSame(204, $response->getStatusCode());
    }
}
