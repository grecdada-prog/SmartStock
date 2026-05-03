<?php

use Illuminate\Support\Facades\Schedule;
use App\Services\SessionManager;

// Nettoyer les sessions expirées toutes les 15 minutes
Schedule::call(function () {
    SessionManager::cleanExpiredSessions();
})->everyFifteenMinutes()->name('clean-expired-sessions')->withoutOverlapping();

Schedule::command('cash-registers:close-daily')
    ->dailyAt('23:00')
    ->timezone('Africa/Douala')
    ->name('close-seller-cash-registers')
    ->withoutOverlapping();

// Alternative : Si vous préférez utiliser la commande
// Schedule::command('sessions:clean')->everyFifteenMinutes();
