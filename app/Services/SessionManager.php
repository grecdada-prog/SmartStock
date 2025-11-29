<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActiveSession;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class SessionManager
{
    /**
     * Vérifier et nettoyer les sessions expirées
     */
    public static function cleanExpiredSessions($minutes = 120)
    {
        $expiredTime = now()->subMinutes($minutes);

        $expiredSessions = ActiveSession::where('last_activity', '<', $expiredTime)->get();

        foreach ($expiredSessions as $session) {
            ActivityLog::create([
                'user_id' => $session->user_id,
                'action' => 'session_expired',
                'description' => 'Session expirée automatiquement',
                'ip_address' => $session->ip_address,
            ]);

            $session->delete();
        }

        return $expiredSessions->count();
    }

    /**
     * Obtenir les utilisateurs actuellement en ligne
     */
    public static function getOnlineUsers($role = null)
    {
        $query = User::whereHas('activeSessions', function ($query) {
            $query->where('last_activity', '>', now()->subMinutes(5));
        });

        if ($role) {
            $query->role($role);
        }

        return $query->get();
    }

    /**
     * Déconnecter un utilisateur de toutes ses sessions
     */
    public static function logoutUserFromAllSessions($userId, $reason = 'Déconnexion forcée')
    {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => 'forced_logout',
            'description' => $reason,
            'ip_address' => request()->ip(),
        ]);

        ActiveSession::where('user_id', $userId)->delete();

        User::find($userId)->update(['session_id' => null]);

        return true;
    }

    /**
     * Obtenir le nombre d'utilisateurs en ligne par rôle
     */
    public static function getOnlineCountByRole()
    {
        return [
            'managers' => self::getOnlineUsers('manager')->count(),
            'sellers' => self::getOnlineUsers('seller')->count(),
            'total' => self::getOnlineUsers()->count(),
        ];
    }

    /**
     * Vérifier si un utilisateur est en ligne
     */
    public static function isUserOnline($userId)
    {
        return ActiveSession::where('user_id', $userId)
            ->where('last_activity', '>', now()->subMinutes(5))
            ->exists();
    }
}