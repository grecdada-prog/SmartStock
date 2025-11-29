<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\ActiveSession;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Vérifier si l'utilisateur est actif
            if (!$user->is_active) {
                // Logger la tentative d'accès
                \App\Models\ActivityLog::log(
                    'blocked_access',
                    'Tentative d\'accès avec un compte désactivé',
                    'User',
                    $user->id
                );

                // Supprimer les sessions actives
                ActiveSession::where('user_id', $user->id)->delete();

                // Déconnecter l'utilisateur
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Votre compte a été désactivé. Contactez l\'administrateur.');
            }
        }

        return $next($request);
    }
}