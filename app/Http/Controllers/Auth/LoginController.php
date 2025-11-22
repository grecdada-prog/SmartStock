<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    protected $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Afficher le formulaire de connexion
     */
    public function show()
    {
        return view('auth.login');
    }

    /**
     * Traiter la connexion
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Enregistrer la tentative échouée
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->authService->recordLoginAttempt($credentials['email'], false);
            
            $failedCount = $this->authService->getFailedAttemptCount($credentials['email']);
            
            if ($failedCount >= 3 && $user && $user->isVendeur()) {
                return back()
                    ->withInput($request->only('email'))
                    ->with('error', '⚠️ Compte vendeur désactivé suite à 3 tentatives échouées. Contactez le gérant pour réactiver votre compte.');
            }

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Identifiants incorrects. Tentatives restantes: ' . (3 - $failedCount));
        }

        // Vérifier si le compte est actif
        if (!$user->is_active) {
            Log::warning('Tentative de connexion avec compte désactivé', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return back()
                ->with('error', 'Votre compte a été désactivé. Contactez l\'administrateur.');
        }

        // Réinitialiser les tentatives échouées
        $this->authService->resetFailedAttempts($credentials['email']);

        // IMPORTANT: Régénérer la session AVANT de connecter l'utilisateur
        $request->session()->regenerate();

        // Connecter l'utilisateur
        Auth::login($user, $request->boolean('remember'));

        // IMPORTANT: Créer la session utilisateur APRÈS la connexion
        $this->authService->createUserSession($user);

        // Enregistrer la tentative réussie
        $this->authService->recordLoginAttempt($credentials['email'], true);

        Log::info('Connexion réussie', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->intended($user->getDashboardRoute())
            ->with('success', 'Bienvenue ' . $user->name . ' !');
    }

    /**
     * Déconnecter l'utilisateur
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $this->authService->closeUserSession($user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Vous avez été déconnecté avec succès.');
    }
}