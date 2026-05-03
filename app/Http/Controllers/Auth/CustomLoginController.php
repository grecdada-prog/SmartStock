<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ActivityLog;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use PragmaRX\Google2FA\Google2FA;

class CustomLoginController extends Controller
{
    /**
     * Afficher le formulaire de connexion Super Admin
     */
    public function showSuperAdminLogin()
    {
        return view('auth.superadmin-login');
    }

    /**
     * Afficher le formulaire de connexion Manager/Seller
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Authentification Super Admin
     */
    public function superAdminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Limiter les tentatives de connexion
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();

            // Vérifier si l'utilisateur est Super Admin
            if (!$user->hasRole('super_admin')) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Accès non autorisé.',
                ]);
            }

            // Vérifier si le compte est actif
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Votre compte a été désactivé.',
                ]);
            }

            // Vérifier si 2FA est activé
            if ($user->google2fa_enabled) {
                Auth::logout();
                $request->session()->put('2fa:user:id', $user->id);
                return redirect()->route('2fa.verify');
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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Limiter les tentatives de connexion
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();

            // Ce formulaire est réservé aux comptes gérant et vendeur.
            if (!$user->hasAnyRole(['manager', 'seller'])) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Accès non autorisé pour ce compte.',
                ])->onlyInput('email');
            }

            // Vérifier si le compte est actif
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Votre compte a été désactivé.',
                ]);
            }

            // Vérifier si 2FA est activé
            if ($user->google2fa_enabled) {
                Auth::logout();
                $request->session()->put('2fa:user:id', $user->id);
                return redirect()->route('2fa.verify');
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
        if (!session()->has('2fa:user:id')) {
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
            'one_time_password' => 'required|numeric',
        ]);

        $userId = session('2fa:user:id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'error' => 'Session expirée. Veuillez vous reconnecter.',
            ]);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            // Authentifier l'utilisateur
            Auth::login($user);
            $request->session()->regenerate();
            session()->forget('2fa:user:id');
            session()->forget('2fa:role');

            // Marquer que l'utilisateur vient de se connecter
            $request->session()->put('just_logged_in', true);
            $request->session()->put('navigation_allowed', true);

            // Déclencher l'événement de connexion
            event(new UserLoggedIn($user, $request->ip(), $request->userAgent()));

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
        $google2fa = new Google2FA();

        if (!$user->google2fa_secret) {
            $secret = $google2fa->generateSecretKey();
            $user->google2fa_secret = $secret;
            $user->save();
        }

        $QR_Image = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        return view('auth.2fa-setup', [
            'QR_Image' => $QR_Image,
            'secret' => $user->google2fa_secret,
        ]);
    }

    /**
     * Activer 2FA
     */
    public function enable2FA(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|numeric',
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            $user->google2fa_enabled = true;
            $user->save();

            ActivityLog::log(
                '2fa_enabled',
                'Authentification à deux facteurs activée',
                'User',
                $user->id
            );

            return redirect()->back()->with('success', 'Authentification à deux facteurs activée avec succès.');
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

        if (!Hash::check($request->password, $user->password)) {
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
            'email' => 'Trop de tentatives de connexion. Veuillez réessayer dans ' . $seconds . ' secondes.',
        ])->onlyInput('email')->with('lockout_seconds', $seconds);
    }

    protected function fireLockoutEvent(Request $request)
    {
        event(new \Illuminate\Auth\Events\Lockout($request));
    }
}
