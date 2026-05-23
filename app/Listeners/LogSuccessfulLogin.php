<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\ActivityLog;
use App\Models\ActiveSession;
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

        try {
            ActiveSession::updateOrCreate(
                [
                    'user_id' => $event->user->id,
                    'session_id' => request()->session()->getId(),
                ],
                [
                    'ip_address' => $event->ipAddress,
                    'user_agent' => $event->userAgent,
                    'last_activity' => now(),
                ]
            );
        } catch (Throwable $exception) {
            Log::warning('Unable to register active session.', [
                'user_id' => $event->user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
