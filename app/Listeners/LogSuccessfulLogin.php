<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogSuccessfulLogin
{
    public function handle(UserLoggedIn $event): void
    {
        try {
            ActivityLog::create([
                'user_id' => $event->user->id,
                'action' => 'login',
                'description' => 'Connexion reussie',
                'ip_address' => $event->ipAddress,
                'properties' => [
                    'user_agent' => $event->userAgent,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to write login activity log.', [
                'user_id' => $event->user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
