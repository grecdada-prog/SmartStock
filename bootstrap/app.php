<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middlewares globaux pour le groupe 'web'
        $middleware->web(append: [
            \App\Http\Middleware\CheckInactivity::class,
            \App\Http\Middleware\SingleSessionMiddleware::class,
            \App\Http\Middleware\CheckUserActive::class,
            \App\Http\Middleware\PreventDirectAccess::class,
        ]);

        // Middlewares avec alias
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'log.activity' => \App\Http\Middleware\LogActivity::class,
            'no.direct.access' => \App\Http\Middleware\RestrictDirectNavigation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
