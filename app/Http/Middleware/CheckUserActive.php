<?php

namespace App\Http\Middleware;

use App\Models\ActiveSession;
use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->is_active) {
                ActivityLog::log(
                    'blocked_access',
                    'Tentative d acces avec un compte desactive',
                    'User',
                    $user->id
                );

                ActiveSession::where('user_id', $user->id)->delete();

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Votre compte a ete desactive. Contactez l administrateur.',
                        'redirect' => route('login'),
                    ], 403);
                }

                return redirect()
                    ->route('login')
                    ->with('error', 'Votre compte a ete desactive. Contactez l administrateur.');
            }
        }

        return $next($request);
    }
}
