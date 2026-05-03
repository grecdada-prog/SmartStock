<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SessionManager;

class CleanExpiredSessions extends Command
{
    protected $signature = 'sessions:clean {--minutes= : Minutes of inactivity before session expires}';
    protected $description = 'Clean expired user sessions';

    public function handle()
    {
        $minutes = $this->option('minutes') ?: (int) config('session.lifetime', 10);
        
        $this->info("Nettoyage des sessions expirées (inactivité > {$minutes} minutes)...");
        
        $count = SessionManager::cleanExpiredSessions($minutes);
        
        $this->info("{$count} session(s) expirée(s) supprimée(s).");
        
        return 0;
    }
}
