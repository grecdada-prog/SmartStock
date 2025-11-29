<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SessionManager;

class CleanExpiredSessions extends Command
{
    protected $signature = 'sessions:clean {--minutes=120 : Minutes of inactivity before session expires}';
    protected $description = 'Clean expired user sessions';

    public function handle()
    {
        $minutes = $this->option('minutes');
        
        $this->info("Nettoyage des sessions expirées (inactivité > {$minutes} minutes)...");
        
        $count = SessionManager::cleanExpiredSessions($minutes);
        
        $this->info("{$count} session(s) expirée(s) supprimée(s).");
        
        return 0;
    }
}