<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'payments/monetbil/callback',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CheckInactivity::class,
            \App\Http\Middleware\SingleSessionMiddleware::class,
            \App\Http\Middleware\CheckUserActive::class,
            \App\Http\Middleware\PreventDirectAccess::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'log.activity' => \App\Http\Middleware\LogActivity::class,
            'no.direct.access' => \App\Http\Middleware\RestrictDirectNavigation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre session a expire. Veuillez vous reconnecter.',
                    'redirect' => route('login', ['inactive' => 1]),
                ], 401);
            }

            return null;
        });

        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            $loginUrl = route('login', ['inactive' => 1]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre session a expire. Veuillez vous reconnecter.',
                    'redirect' => $loginUrl,
                ], 419);
            }

            return redirect($loginUrl);
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if ($request->expectsJson()) {
                $retryAfter = (int) ($exception->getHeaders()['Retry-After'] ?? 60);

                return response()->json([
                    'success' => false,
                    'message' => 'Trop de tentatives. Veuillez patienter quelques secondes puis reessayer.',
                    'retry_after' => $retryAfter,
                ], 429);
            }

            return null;
        });
    })->create();
