<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\ActivityLog;

class LogSuccessfulLogin
{
    public function handle(UserLoggedIn $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'action' => 'login',
            'description' => 'Connexion réussie',
            'ip_address' => $event->ipAddress,
            'properties' => [
                'user_agent' => $event->userAgent,
            ],
        ]);
    }
}