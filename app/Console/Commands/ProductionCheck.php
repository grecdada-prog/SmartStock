<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProductionCheck extends Command
{
    protected $signature = 'production:check';

    protected $description = 'Check production requirements that commonly cause HTTP 500 errors.';

    public function handle(): int
    {
        $failed = false;

        $this->info('SmartStore production check');
        $this->newLine();

        $failed = $this->checkEnvironment() || $failed;
        $failed = $this->checkWritablePaths() || $failed;
        $failed = $this->checkCache() || $failed;
        $failed = $this->checkDatabase() || $failed;
        $failed = $this->checkViews() || $failed;

        $this->newLine();

        if ($failed) {
            $this->error('Production check failed. Fix the errors above before testing /login.');

            return self::FAILURE;
        }

        $this->info('Production check passed. The Laravel runtime, cache, storage and database schema look ready.');

        return self::SUCCESS;
    }

    private function checkEnvironment(): bool
    {
        $failed = false;

        $this->line('Environment');
        $this->table(
            ['Key', 'Value'],
            [
                ['APP_ENV', Config::get('app.env')],
                ['APP_DEBUG', Config::get('app.debug') ? 'true' : 'false'],
                ['APP_URL', Config::get('app.url')],
                ['DB_CONNECTION', Config::get('database.default')],
                ['DB_HOST', Config::get('database.connections.mysql.host')],
                ['CACHE_STORE', Config::get('cache.default')],
                ['SESSION_DRIVER', Config::get('session.driver')],
            ]
        );

        if (Config::get('app.debug')) {
            $this->warn('APP_DEBUG should be false in production.');
            $failed = true;
        }

        if (Config::get('database.connections.mysql.host') === '127.0.0.1') {
            $this->warn('DB_HOST is 127.0.0.1. On Hostinger, localhost is usually required for local MySQL users.');
        }

        return $failed;
    }

    private function checkWritablePaths(): bool
    {
        $failed = false;

        $this->line('Writable paths');

        foreach ([
            storage_path(),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $path) {
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }

            if (! is_writable($path)) {
                $this->error("Not writable: {$path}");
                $failed = true;
            } else {
                $this->info("Writable: {$path}");
            }
        }

        return $failed;
    }

    private function checkCache(): bool
    {
        $this->line('Cache');

        try {
            Cache::put('SmartStore-production-check', now()->toISOString(), 60);
            Cache::forget('SmartStore-production-check');
            $this->info('Cache write/read OK.');

            return false;
        } catch (Throwable $e) {
            $this->error('Cache failed: '.$e->getMessage());

            return true;
        }
    }

    private function checkDatabase(): bool
    {
        $failed = false;

        $this->line('Database');

        try {
            DB::connection()->getPdo();
            $this->info('Database connection OK.');
        } catch (Throwable $e) {
            $this->error('Database connection failed: '.$e->getMessage());

            return true;
        }

        foreach ([
            'users',
            'roles',
            'model_has_roles',
            'activity_logs',
            'active_sessions',
            'migrations',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing table: {$table}");
                $failed = true;
            } else {
                $this->info("Table OK: {$table}");
            }
        }

        foreach ([
            'users' => ['email', 'password', 'is_active', 'google2fa_enabled', 'created_by'],
            'activity_logs' => ['user_id', 'action', 'description', 'ip_address', 'properties'],
            'active_sessions' => ['user_id', 'session_id', 'ip_address', 'user_agent', 'last_activity'],
        ] as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $this->error("Missing column: {$table}.{$column}");
                    $failed = true;
                }
            }
        }

        try {
            DB::table('users')->limit(1)->exists();
            $this->info('Users table query OK.');
        } catch (Throwable $e) {
            $this->error('Users table query failed: '.$e->getMessage());
            $failed = true;
        }

        return $failed;
    }

    private function checkViews(): bool
    {
        $this->line('Views');

        try {
            Artisan::call('view:clear');
            Artisan::call('view:cache');
            $this->info('Blade views compile OK.');

            return false;
        } catch (Throwable $e) {
            $this->error('Blade view compilation failed: '.$e->getMessage());
            $this->comment('Run php artisan logs:recent --lines=80 for the latest Laravel error details.');

            return true;
        }
    }
}
