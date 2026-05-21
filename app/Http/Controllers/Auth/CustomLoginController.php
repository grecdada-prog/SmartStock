<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;

class CustomLoginController extends Controller
{
    /**
     * Afficher le formulaire de connexion Super Admin
     */
    public function showSuperAdminLogin()
    {
        return $this->noStoreResponse('auth.superadmin-login');
    }

    /**
     * Afficher le formulaire de connexion Manager/Seller
     */
    public function showLogin()
    {
        return $this->noStoreResponse('auth.login');
    }

    /**
     * Authentification Super Admin
     */
    public function superAdminLogin(Request $request)
    {
        $this->normalizeContactInputs($request, [], ['email']);

        $request->validate([
            'email' => ['required', 'email:rfc,filter', 'regex:/^(?!.*\.\.)[A-Z0-9](?:[A-Z0-9._%+\-]{0,62}[A-Z0-9])?@(?:[A-Z0-9](?:[A-Z0-9\-]{0,61}[A-Z0-9])?\.)+[A-Z]{2,63}$/i'],
            'password' => 'required',
        ], $this->contactValidationMessages());

        // Limiter les tentatives de connexion
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();

            // Vérifier si l'utilisateur est Super Admin
            if (! $user->hasRole('super_admin')) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Accès non autorisé.',
                ]);
            }

            // Vérifier si le compte est actif
            if (! $user->is_active) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Votre compte a été désactivé.',
                ]);
            }

            // Vérifier si 2FA est activé
            if ($user->google2fa_enabled) {
                return $this->beginTwoFactorChallenge($request, $user);
            }

            // Régénérer la session pour éviter la fixation de session
            $request->session()->regenerate();

            // Marquer que l'utilisateur vient de se connecter
            $request->session()->put('just_logged_in', true);
            $request->session()->put('navigation_allowed', true);

            // Déclencher l'événement de connexion
            event(new UserLoggedIn($user, $request->ip(), $request->userAgent()));

            // Effacer le compteur de tentatives
            $this->clearLoginAttempts($request);

            return redirect()->intended(route('superadmin.dashboard'));
        }

        // Incrémenter les tentatives échouées
        $this->incrementLoginAttempts($request);

        return back()->withErrors([
            'email' => 'Les identifiants ne correspondent pas.',
        ])->onlyInput('email');
    }

    /**
     * Authentification Manager/Seller
     */
    public function login(Request $request)
    {
        $this->normalizeContactInputs($request, [], ['email']);

        $request->validate([
            'email' => ['required', 'email:rfc,filter', 'regex:/^(?!.*\.\.)[A-Z0-9](?:[A-Z0-9._%+\-]{0,62}[A-Z0-9])?@(?:[A-Z0-9](?:[A-Z0-9\-]{0,61}[A-Z0-9])?\.)+[A-Z]{2,63}$/i'],
            'password' => 'required',
        ], $this->contactValidationMessages());

        // Limiter les tentatives de connexion
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();

            // Ce formulaire est réservé aux comptes gérant et vendeur.
            if (! $user->hasAnyRole(['manager', 'seller'])) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Accès non autorisé pour ce compte.',
                ])->onlyInput('email');
            }

            // Vérifier si le compte est actif
            if (! $user->is_active) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Votre compte a été désactivé.',
                ]);
            }

            // Vérifier si 2FA est activé
            if ($user->google2fa_enabled) {
                return $this->beginTwoFactorChallenge($request, $user);
            }

            // Régénérer la session
            $request->session()->regenerate();

            // Marquer que l'utilisateur vient de se connecter
            $request->session()->put('just_logged_in', true);
            $request->session()->put('navigation_allowed', true);

            // Déclencher l'événement de connexion
            event(new UserLoggedIn($user, $request->ip(), $request->userAgent()));

            // Effacer le compteur de tentatives
            $this->clearLoginAttempts($request);

            // Rediriger selon le rôle
            if ($user->hasRole('manager')) {
                return redirect()->intended(route('manager.dashboard'));
            } elseif ($user->hasRole('seller')) {
                return redirect()->intended(route('seller.dashboard'));
            }
        }

        // Incrémenter les tentatives échouées
        $this->incrementLoginAttempts($request);

        return back()->withErrors([
            'email' => 'Les identifiants ne correspondent pas.',
        ])->onlyInput('email');
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        // Déclencher l'événement de déconnexion
        if ($user) {
            event(new UserLoggedOut($user));
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('message', 'Vous avez été déconnecté avec succès.');
    }

    /**
     * Afficher la page de vérification 2FA
     */
    public function show2FAVerify()
    {
        if (! session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa-verify');
    }

    /**
     * Vérifier le code 2FA
     */
    public function verify2FA(Request $request)
    {
        $request->validate([
            'one_time_password' => ['required', 'regex:/^\d{1,6}$/'],
        ]);

        $userId = session('2fa:user:id');
        $remember = (bool) session('2fa:remember', false);
        $user = User::find($userId);

        if (! $user || ! $user->google2fa_enabled || blank($user->google2fa_secret)) {
            session()->forget(['2fa:user:id', '2fa:remember']);

            return redirect()->route('login')->withErrors([
                'error' => 'Session expirée. Veuillez vous reconnecter.',
            ]);
        }

        $google2fa = new Google2FA;
        $oneTimePassword = $this->normalizeOneTimePassword($request->input('one_time_password'));
        $valid = $google2fa->verifyKey($user->google2fa_secret, $oneTimePassword, 2);

        if ($valid) {
            // Authentifier l'utilisateur
            Auth::login($user, $remember);
            $request->session()->regenerate();
            session()->forget(['2fa:user:id', '2fa:remember']);

            // Marquer que l'utilisateur vient de se connecter
            $request->session()->put('just_logged_in', true);
            $request->session()->put('navigation_allowed', true);

            // Déclencher l'événement de connexion
            event(new UserLoggedIn($user, $request->ip(), $request->userAgent()));
            $this->clearLoginAttempts($request);

            // Rediriger selon le rôle
            if ($user->hasRole('super_admin')) {
                return redirect()->route('superadmin.dashboard');
            } elseif ($user->hasRole('manager')) {
                return redirect()->route('manager.dashboard');
            } elseif ($user->hasRole('seller')) {
                return redirect()->route('seller.dashboard');
            }
        }

        return back()->withErrors([
            'one_time_password' => 'Code de vérification invalide.',
        ]);
    }

    /**
     * Afficher la page de configuration 2FA
     */
    public function show2FASetup()
    {
        $user = Auth::user();
        $google2fa = new Google2FAQRCode(null, new SvgImageBackEnd);
        $QR_Image = null;
        $secret = $user->google2fa_secret;

        if (! $user->google2fa_enabled && ! $user->google2fa_secret) {
            $secret = $google2fa->generateSecretKey();
            $user->google2fa_secret = $secret;
            $user->save();
        }

        if (! $user->google2fa_enabled) {
            $QR_Image = $google2fa->getQRCodeInline(
                config('app.name'),
                $user->email,
                $user->google2fa_secret
            );
        }

        return view('auth.2fa-setup', [
            'QR_Image' => $QR_Image,
            'secret' => $user->google2fa_secret,
            'user' => $user,
        ]);
    }

    /**
     * Activer 2FA
     */
    public function enable2FA(Request $request)
    {
        $request->validate([
            'one_time_password' => ['required', 'regex:/^\d{1,6}$/'],
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA;
        $oneTimePassword = $this->normalizeOneTimePassword($request->input('one_time_password'));

        $valid = $google2fa->verifyKey($user->google2fa_secret, $oneTimePassword, 2);

        if ($valid) {
            User::whereKey($user->id)->update([
                'google2fa_enabled' => true,
            ]);
            $user->forceFill(['google2fa_enabled' => true]);

            ActivityLog::log(
                '2fa_enabled',
                'Authentification à deux facteurs activée',
                'User',
                $user->id
            );

            event(new UserLoggedOut($user));

            Auth::logout();
            $request->session()->flush();
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            $request->session()->flash('success', 'Authentification a deux facteurs activee. Reconnectez-vous pour verifier votre nouvelle protection.');

            return redirect()->route('login');
        }

        return back()->withErrors([
            'one_time_password' => 'Code de vérification invalide.',
        ]);
    }

    /**
     * Désactiver 2FA
     */
    public function disable2FA(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Mot de passe incorrect.',
            ]);
        }

        $user->google2fa_enabled = false;
        $user->google2fa_secret = null;
        $user->save();

        ActivityLog::log(
            '2fa_disabled',
            'Authentification à deux facteurs désactivée',
            'User',
            $user->id
        );

        return redirect()->back()->with('success', 'Authentification à deux facteurs désactivée.');
    }

    /**
     * Limiter les tentatives de connexion (throttle)
     */
    protected function hasTooManyLoginAttempts(Request $request)
    {
        return app(\Illuminate\Cache\RateLimiter::class)->tooManyAttempts(
            $this->throttleKey($request),
            5 // 5 tentatives maximum
        );
    }

    protected function incrementLoginAttempts(Request $request)
    {
        app(\Illuminate\Cache\RateLimiter::class)->hit(
            $this->throttleKey($request),
            60 // Bloquer pendant 60 secondes
        );
    }

    protected function clearLoginAttempts(Request $request)
    {
        app(\Illuminate\Cache\RateLimiter::class)->clear(
            $this->throttleKey($request)
        );
    }

    protected function throttleKey(Request $request)
    {
        return strtolower((string) $request->input('email')).'|'.$request->ip();
    }

    protected function sendLockoutResponse(Request $request)
    {
        $seconds = app(\Illuminate\Cache\RateLimiter::class)->availableIn(
            $this->throttleKey($request)
        );

        return back()->withErrors([
            'email' => 'Trop de tentatives de connexion. Veuillez réessayer dans '.$seconds.' secondes.',
        ])->onlyInput('email')->with('lockout_seconds', $seconds);
    }

    protected function fireLockoutEvent(Request $request)
    {
        event(new \Illuminate\Auth\Events\Lockout($request));
    }

    private function beginTwoFactorChallenge(Request $request, User $user)
    {
        $userId = $user->id;
        $remember = $request->filled('remember');

        Auth::logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->put('2fa:user:id', $userId);
        $request->session()->put('2fa:remember', $remember);

        return redirect()->route('2fa.verify');
    }

    private function normalizeOneTimePassword(mixed $value): string
    {
        return str_pad(preg_replace('/\D/', '', (string) $value), 6, '0', STR_PAD_LEFT);
    }

    private function noStoreResponse(string $view)
    {
        return response()->view($view)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
        ]);
    }
}

