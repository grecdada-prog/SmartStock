<?php

namespace App\Http\Controllers;

use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    /**
     * Fermer la session de l'utilisateur (endpoint API)
     */
    public function closeSession(Request $request)
    {
        $userId = $request->input('user_id');
        
        if ($userId) {
            // Fermer toutes les sessions actives de l'utilisateur
            $sessions = UserSession::where('user_id', $userId)
                ->where('is_active', true)
                ->get();

            foreach ($sessions as $session) {
                $session->closeSession();
            }

            Log::info('Session fermée via API', [
                'user_id' => $userId,
                'ip' => $request->ip(),
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Nettoyer les anciennes sessions (à exécuter via scheduler)
     */
    public function cleanupOldSessions()
    {
        // Supprimer les sessions fermées depuis plus de 7 jours
        $deleted = UserSession::where('is_active', false)
            ->where('logged_out_at', '<', now()->subDays(7))
            ->delete();

        Log::info('Nettoyage des anciennes sessions', [
            'deleted_count' => $deleted,
        ]);

        return response()->json([
            'status' => 'success',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Vérifier si la session est toujours valide
     */
    public function checkSession(Request $request)
    {
        if (!auth()->check()) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Session invalide',
            ], 401);
        }

        $user = auth()->user();
        $sessionId = session()->getId();

        $userSession = UserSession::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->first();

        if (!$userSession) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Session non trouvée',
            ], 401);
        }

        return response()->json([
            'status' => 'valid',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }
}