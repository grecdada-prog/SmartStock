<?php

use App\Services\SessionManager;
use Illuminate\Support\Facades\Schedule;

// Nettoyer les sessions expirées toutes les 15 minutes
Schedule::call(function () {
    SessionManager::cleanExpiredSessions();
})->everyFifteenMinutes()->name('clean-expired-sessions')->withoutOverlapping();

Schedule::command('cash-registers:close-daily')
    ->dailyAt('21:30')
    ->timezone('Africa/Douala')
    ->name('close-seller-cash-registers')
    ->withoutOverlapping();
