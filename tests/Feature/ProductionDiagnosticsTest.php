<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionDiagnosticsTest extends TestCase
{
    public function test_recent_logs_command_outputs_latest_log_lines(): void
    {
        $logPath = storage_path('logs/laravel.log');
        $marker = 'smartstock-diagnostic-test-'.uniqid();

        File::ensureDirectoryExists(dirname($logPath));
        File::append($logPath, PHP_EOL.$marker.PHP_EOL);

        $this->artisan('logs:recent', ['--lines' => 20])
            ->expectsOutputToContain($marker)
            ->assertSuccessful();
    }
}
