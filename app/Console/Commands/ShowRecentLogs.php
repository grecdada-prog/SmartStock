<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SplFileObject;

class ShowRecentLogs extends Command
{
    protected $signature = 'logs:recent {--lines=120 : Number of lines to display}';

    protected $description = 'Show the latest Laravel log lines for quick production diagnostics.';

    public function handle(): int
    {
        $path = storage_path('logs/laravel.log');
        $lines = max(20, (int) $this->option('lines'));

        if (! File::exists($path)) {
            $this->warn("No Laravel log file found at {$path}");

            return self::SUCCESS;
        }

        $file = new SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);

        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines);
        $recentRows = [];

        $file->seek($startLine);

        while (! $file->eof()) {
            $line = rtrim((string) $file->fgets());

            if ($line !== '') {
                $recentRows[] = $line;
            }
        }

        $this->line("Last {$lines} lines from {$path}");
        $this->newLine();
        $this->line(implode(PHP_EOL, $recentRows));

        return self::SUCCESS;
    }
}
