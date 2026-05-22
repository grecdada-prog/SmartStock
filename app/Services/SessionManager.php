<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActiveSession;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SessionManager
{
    public const PRESENCE_TIMEOUT_SECONDS = 45;
    public const SAME_CLIENT_RECONNECT_SECONDS = 8;

    public static function presenceTimeoutSeconds(): int
    {
        return max(15, (int) config('session.presence_timeout_seconds', self::PRESENCE_TIMEOUT_SECONDS));
    }

    public static function sameClientReconnectSeconds(): int
    {
        return max(5, (int) config('session.same_client_reconnect_seconds', self::SAME_CLIENT_RECONNECT_SECONDS));
    }

    public static function cleanExpiredPresence(?int $seconds = null): int
    {
        $seconds = $seconds ?? self::presenceTimeoutSeconds();

        return ActiveSession::where('last_activity', '<', now()->subSeconds($seconds))->delete();
    }

    public static function hasBlockingSession(User $user, string $sessionId, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        self::cleanExpiredPresence();

        $sessions = ActiveSession::where('user_id', $user->id)
            ->where('session_id', '!=', $sessionId)
            ->get();

        foreach ($sessions as $session) {
            $sameClient = $ipAddress
                && $userAgent
                && hash_equals((string) $session->ip_address, (string) $ipAddress)
                && hash_equals((string) $session->user_agent, (string) $userAgent);

            if ($sameClient && $session->last_activity->lt(now()->subSeconds(self::sameClientReconnectSeconds()))) {
                $session->delete();
                continue;
            }

            return true;
        }

        return false;
    }

    public static function touch(User $user, string $sessionId, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        ActiveSession::updateOrCreate(
            [
                'user_id' => $user->id,
                'session_id' => $sessionId,
            ],
            [
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'last_activity' => now(),
            ]
        );
    }

    public static function isForcedLogoutSession(string $sessionId): bool
    {
        return Cache::has(self::forcedLogoutCacheKey($sessionId));
    }

    public static function forgetForcedLogoutSession(string $sessionId): void
    {
        Cache::forget(self::forcedLogoutCacheKey($sessionId));
    }

    /**
     * Vérifier et nettoyer les sessions expirées
     */
    public static function cleanExpiredSessions($minutes = null)
    {
        $minutes = $minutes ?? (int) config('session.lifetime', 10);
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
            $query->where('last_activity', '>', now()->subSeconds(self::presenceTimeoutSeconds()));
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
        $sessionIds = ActiveSession::where('user_id', $userId)
            ->pluck('session_id')
            ->filter()
            ->values();

        ActivityLog::create([
            'user_id' => $userId,
            'action' => 'forced_logout',
            'description' => $reason,
            'ip_address' => request()->ip(),
        ]);

        ActiveSession::where('user_id', $userId)->delete();

        $sessionIds->each(function (string $sessionId): void {
            Cache::put(self::forcedLogoutCacheKey($sessionId), true, now()->addMinutes((int) config('session.lifetime', 120) + 5));
            self::destroyStoredSession($sessionId);
        });

        User::whereKey($userId)->update(['session_id' => null]);

        return true;
    }

    private static function forcedLogoutCacheKey(string $sessionId): string
    {
        return 'forced_logout_session:'.$sessionId;
    }

    private static function destroyStoredSession(string $sessionId): void
    {
        try {
            app('session')->driver()->getHandler()->destroy($sessionId);
        } catch (\Throwable) {
            // Continue with explicit fallbacks below.
        }

        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))->where('id', $sessionId)->delete();
        }

        if (config('session.driver') === 'file') {
            $path = rtrim((string) config('session.files'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$sessionId;

            if (File::exists($path)) {
                File::delete($path);
            }
        }
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
            ->where('last_activity', '>', now()->subSeconds(self::presenceTimeoutSeconds()))
            ->exists();
    }
}
