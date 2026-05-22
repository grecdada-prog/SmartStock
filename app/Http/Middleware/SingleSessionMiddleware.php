<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Services\SessionManager as ActiveSessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SingleSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = Session::getId();

            if (ActiveSessionManager::isForcedLogoutSession($currentSessionId)) {
                ActiveSessionManager::forgetForcedLogoutSession($currentSessionId);

                ActivityLog::log(
                    'forced_logout_applied',
                    'Session fermee par un administrateur',
                    'User',
                    $user->id
                );

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = 'Votre session a ete fermee par votre administrateur. Veuillez vous reconnecter.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'redirect' => route('login'),
                    ], 401);
                }

                return redirect()->route('login')->with('message', $message);
            }

            $activeSessionElsewhere = ActiveSessionManager::hasBlockingSession(
                $user,
                $currentSessionId,
                $request->ip(),
                $request->userAgent()
            );

            if ($activeSessionElsewhere) {
                ActivityLog::log(
                    'session_blocked',
                    'Session fermee car le compte est deja ouvert ailleurs',
                    'User',
                    $user->id
                );

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = 'Ce compte est deja ouvert dans un autre navigateur. Deconnectez-le avant de vous reconnecter.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'redirect' => route('login'),
                    ], 409);
                }

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => $message]);
            }

            ActiveSessionManager::touch($user, $currentSessionId, $request->ip(), $request->userAgent());

            $user->updateLastActivity();
        }

        return $next($request);
    }
}
