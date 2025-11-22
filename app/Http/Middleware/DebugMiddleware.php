<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Log pour debug
            \Illuminate\Support\Facades\Log::debug('Debug Request', [
                'url' => $request->path(),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'session_id' => session()->getId(),
                'is_active' => $user->is_active,
                'has_user_sessions' => \App\Models\UserSession::where('user_id', $user->id)->count(),
            ]);
        }

        return $next($request);
    }
}