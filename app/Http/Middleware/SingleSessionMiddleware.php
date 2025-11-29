<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActiveSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SingleSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = Session::getId();

            // Vérifier s'il existe une session active différente
            $activeSession = ActiveSession::where('user_id', $user->id)
                ->where('session_id', '!=', $currentSessionId)
                ->first();

            if ($activeSession) {
                // Supprimer l'ancienne session
                ActiveSession::where('user_id', $user->id)
                    ->where('session_id', '!=', $currentSessionId)
                    ->delete();

                // Logger cette activité
                \App\Models\ActivityLog::log(
                    'session_replaced',
                    'Session précédente fermée - Nouvelle connexion détectée',
                    'User',
                    $user->id
                );
            }

            // Créer ou mettre à jour la session active
            ActiveSession::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'session_id' => $currentSessionId
                ],
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity' => now(),
                ]
            );

            // Mettre à jour l'activité de l'utilisateur
            $user->updateLastActivity();
        }

        return $next($request);
    }
}