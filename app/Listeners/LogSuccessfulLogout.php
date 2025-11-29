<?php

namespace App\Listeners;

use App\Events\UserLoggedOut;
use App\Models\ActivityLog;
use App\Models\ActiveSession;

class LogSuccessfulLogout
{
    public function handle(UserLoggedOut $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'action' => 'logout',
            'description' => 'Déconnexion réussie',
            'ip_address' => request()->ip(),
        ]);

        // Supprimer la session active
        ActiveSession::where('user_id', $event->user->id)->delete();
    }
}