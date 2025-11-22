<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GerantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!auth()->check()) {
            Log::warning('Tentative d\'accès non authentifié à une zone gérant', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = auth()->user();

        // Vérifier si le compte est actif
        if (!$user->is_active) {
            Log::warning('Tentative d\'accès avec un compte désactivé', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Votre compte a été désactivé. Contactez l\'administrateur.');
        }

        // Vérifier si l'utilisateur est bien un gérant
        if ($user->role !== 'gerant') {
            Log::alert('SÉCURITÉ: Tentative d\'accès non autorisé à une zone gérant', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            // Déconnecter l'utilisateur immédiatement
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Accès refusé. Cette tentative a été enregistrée.');
        }

        // Vérifier que la session n'a pas été compromise
        if ($request->session()->get('user_role') && $request->session()->get('user_role') !== 'gerant') {
            Log::alert('SÉCURITÉ: Session compromise détectée', [
                'user_id' => $user->id,
                'session_role' => $request->session()->get('user_role'),
                'actual_role' => $user->role,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Session invalide. Veuillez vous reconnecter.');
        }

        // Stocker le rôle dans la session pour vérification ultérieure
        $request->session()->put('user_role', 'gerant');

        return $next($request);
    }
}