<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\ActiveSession;

class CheckInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $lastActivity = $user->last_activity;
            $inactivityTimeout = (int) config('session.lifetime', 10);

            if ($request->session()->pull('just_logged_in', false)) {
                $user->updateLastActivity();

                return $next($request);
            }

            // Si l'utilisateur a été inactif trop longtemps
            if ($lastActivity && $lastActivity->diffInMinutes(now()) >= $inactivityTimeout) {
                // Logger la déconnexion automatique
                \App\Models\ActivityLog::log(
                    'auto_logout',
                    'Déconnexion automatique pour inactivité',
                    'User',
                    $user->id
                );

                // Supprimer la session active
                ActiveSession::where('user_id', $user->id)->delete();

                // Déconnecter l'utilisateur
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('message', 'Vous avez été déconnecté pour inactivité.');
            }

            // Mettre à jour la dernière activité
            ActiveSession::where('user_id', $user->id)
                ->where('session_id', $request->session()->getId())
                ->update(['last_activity' => now()]);

            $user->updateLastActivity();
        }

        return $next($request);
    }
}
