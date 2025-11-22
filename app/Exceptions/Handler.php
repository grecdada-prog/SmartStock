<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Afficher la page 403 personnalisée
        if ($this->isHttpException($exception) && $exception->getStatusCode() == 403) {
            return response()->view('errors.403', [], 403);
        }

        // Afficher la page 404 personnalisée
        if ($this->isHttpException($exception) && $exception->getStatusCode() == 404) {
            return response()->view('errors.404', [], 404);
        }

        // Afficher la page 419 personnalisée
        if ($this->isHttpException($exception) && $exception->getStatusCode() == 419) {
            return response()->view('errors.419', [], 419);
        }

        // Afficher la page 500 personnalisée
        if ($this->isHttpException($exception) && $exception->getStatusCode() == 500) {
            return response()->view('errors.500', [], 500);
        }

        return parent::render($request, $exception);
    }
}