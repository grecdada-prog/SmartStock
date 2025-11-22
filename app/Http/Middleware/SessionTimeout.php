<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\UserSession;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            $sessionId = session()->getId();

            // Chercher la session active UNIQUEMENT si on est sur une route protégée
            if ($this->isProtectedRoute($request)) {
                $activeSession = UserSession::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->latest()
                    ->first();

                // Si pas de session active, c'est une tentative suspecte
                if (!$activeSession) {
                    Log::warning('Aucune session active trouvée', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'ip' => $request->ip(),
                    ]);

                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('error', 'Votre session a expiré. Veuillez vous reconnecter.');
                }

                // Vérifier l'inactivité (30 minutes par défaut)
                $lastActivity = session()->get('last_activity');
                $timeout = 30 * 60; // 30 minutes en secondes

                if ($lastActivity && (time() - $lastActivity) > $timeout) {
                    Log::info('Session expirée par inactivité', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'inactivity_minutes' => round((time() - $lastActivity) / 60),
                    ]);

                    // Fermer la session proprement
                    $activeSession->closeSession();

                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('error', 'Votre session a expiré suite à une inactivité prolongée.');
                }
            }

            // Mettre à jour l'heure de la dernière activité
            session()->put('last_activity', time());
        }

        return $next($request);
    }

    /**
     * Vérifier si c'est une route protégée
     */
    private function isProtectedRoute(Request $request): bool
    {
        return $request->is('admin/*') || $request->is('pos/*') || $request->is('dashboard');
    }
}