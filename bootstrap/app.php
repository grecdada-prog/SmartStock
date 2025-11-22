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
        // Middleware globaux pour la sécurité
        $middleware->web(append: [
            \App\Http\Middleware\CheckUserRole::class,
            // SessionTimeout désactivé temporairement pour debug
            // \App\Http\Middleware\SessionTimeout::class,
        ]);

        // Alias pour les middleware de rôles
        $middleware->alias([
            'gerant' => \App\Http\Middleware\GerantMiddleware::class,
            'vendeur' => \App\Http\Middleware\VendeurMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();