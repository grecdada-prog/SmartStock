<?php

use Illuminate\Support\Facades\Schedule;
use App\Services\SessionManager;

// Nettoyer les sessions expirées toutes les 15 minutes
Schedule::call(function () {
    SessionManager::cleanExpiredSessions(120);
})->everyFifteenMinutes()->name('clean-expired-sessions')->withoutOverlapping();

// Alternative : Si vous préférez utiliser la commande
// Schedule::command('sessions:clean')->everyFifteenMinutes();