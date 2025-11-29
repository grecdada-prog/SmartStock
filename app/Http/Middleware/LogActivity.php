<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActivityLog;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Logger uniquement les actions importantes
        if (auth()->check()) {
            $method = $request->method();
            $path = $request->path();

            // Actions à logger
            $loggableActions = ['POST', 'PUT', 'PATCH', 'DELETE'];

            if (in_array($method, $loggableActions)) {
                ActivityLog::log(
                    strtolower($method),
                    "Action {$method} sur {$path}",
                    null,
                    null,
                    [
                        'method' => $method,
                        'path' => $path,
                        'user_agent' => $request->userAgent(),
                    ]
                );
            }
        }

        return $response;
    }
}