<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Nettoyer les anciennes sessions tous les jours à 2h du matin
        $schedule->call(function () {
            \App\Models\UserSession::where('is_active', false)
                ->where('logged_out_at', '<', now()->subDays(7))
                ->delete();

            \Illuminate\Support\Facades\Log::info('Nettoyage des anciennes sessions exécuté');
        })->dailyAt('02:00');

        // Nettoyer les anciennes tentatives de connexion tous les jours
        $schedule->call(function () {
            \App\Models\LoginAttempt::where('created_at', '<', now()->subDays(30))
                ->delete();

            \Illuminate\Support\Facades\Log::info('Nettoyage des anciennes tentatives de connexion exécuté');
        })->dailyAt('02:15');

        // Vérifier les sessions inactive toutes les heures
        $schedule->call(function () {
            $inactiveSessions = \App\Models\UserSession::where('is_active', true)
                ->where('updated_at', '<', now()->subMinutes(35))
                ->get();

            foreach ($inactiveSessions as $session) {
                $session->update(['is_active' => false, 'logged_out_at' => now()]);
            }

            \Illuminate\Support\Facades\Log::info('Vérification des sessions inactives exécutée', [
                'inactive_count' => $inactiveSessions->count(),
            ]);
        })->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}