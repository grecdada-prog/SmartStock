<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDirectAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() || $this->isPublicRequest($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(401, 'Non authentifié.');
        }

        return redirect()->route('login');
    }

    private function isPublicRequest(Request $request): bool
    {
        return $request->is(
            '/',
            'login',
            'superadmin/login',
            '2fa/verify',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'build/*',
            'storage/*',
            'css/*',
            'js/*',
            'images/*'
        );
    }
}
