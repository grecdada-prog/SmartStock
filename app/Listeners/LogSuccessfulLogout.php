<?php

namespace App\Listeners;

use App\Events\UserLoggedOut;
use App\Models\ActivityLog;
use App\Models\ActiveSession;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogSuccessfulLogout
{
    public function handle(UserLoggedOut $event): void
    {
        try {
            ActivityLog::create([
                'user_id' => $event->user->id,
                'action' => 'logout',
                'description' => 'Deconnexion reussie',
                'ip_address' => request()->ip(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to write logout activity log.', [
                'user_id' => $event->user->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        // Supprimer la session active
        ActiveSession::where('user_id', $event->user->id)->delete();
    }
}
