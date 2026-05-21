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
            return response()->json([
                'success' => false,
                'message' => 'Votre session a expire. Veuillez vous reconnecter.',
                'redirect' => route('login', ['inactive' => 1]),
            ], 401);
        }

        return redirect()->route('login');
    }

    private function isPublicRequest(Request $request): bool
    {
        return $request->is(
            '/',
            'csrf-token',
            'login',
            'superadmin/login',
            '2fa/verify',
            'payments/monetbil/callback',
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
