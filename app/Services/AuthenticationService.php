<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\UserSession;
use Illuminate\Support\Facades\Log;

class AuthenticationService
{
    /**
     * Vérifier et enregistrer une tentative de connexion
     */
    public function recordLoginAttempt(string $email, bool $successful = false): void
    {
        LoginAttempt::recordAttempt($email, $successful);

        if (!$successful) {
            $failedCount = LoginAttempt::countFailedAttempts($email);
            
            // Si plus de 3 tentatives échouées, désactiver le compte vendeur
            if ($failedCount >= 3) {
                $user = User::where('email', $email)->first();
                
                if ($user && $user->isVendeur()) {
                    $user->update(['is_active' => false]);
                    
                    Log::alert('SÉCURITÉ: Compte vendeur désactivé suite à 3 tentatives échouées', [
                        'user_id' => $user->id,
                        'email' => $email,
                        'ip' => request()->ip(),
                        'attempts_count' => $failedCount,
                    ]);
                }
            }
        }
    }

    /**
     * Créer une session pour l'utilisateur connecté
     */
    public function createUserSession(User $user): UserSession
    {
        return UserSession::createSession($user);
    }

    /**
     * Fermer la session de l'utilisateur
     */
    public function closeUserSession(User $user): void
    {
        $session = UserSession::getActiveSession($user->id);
        
        if ($session) {
            $session->closeSession();
        }

        Log::info('Utilisateur déconnecté', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isUserLoggedIn(User $user): bool
    {
        return UserSession::isUserActive($user->id);
    }

    /**
     * Compter les tentatives échouées
     */
    public function getFailedAttemptCount(string $email): int
    {
        return LoginAttempt::countFailedAttempts($email);
    }

    /**
     * Réinitialiser les tentatives échouées
     */
    public function resetFailedAttempts(string $email): void
    {
        LoginAttempt::where('email', $email)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->delete();
    }
}