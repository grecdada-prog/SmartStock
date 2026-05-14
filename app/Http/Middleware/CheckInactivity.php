<?php

namespace App\Http\Middleware;

use App\Models\ActiveSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $lastActivity = $user->last_activity;
        $inactivityTimeout = (int) config('session.lifetime', 10);

        if ($request->session()->pull('just_logged_in', false)) {
            $this->touchActivity($request, $user);

            return $next($request);
        }

        if ($lastActivity && $lastActivity->diffInMinutes(now()) >= $inactivityTimeout) {
            \App\Models\ActivityLog::log(
                'auto_logout',
                'Deconnexion automatique pour inactivite',
                'User',
                $user->id
            );

            ActiveSession::where('user_id', $user->id)->delete();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('message', 'Vous avez ete deconnecte pour inactivite.');
        }

        $this->touchActivity($request, $user);

        return $next($request);
    }

    private function touchActivity(Request $request, $user): void
    {
        ActiveSession::where('user_id', $user->id)
            ->where('session_id', $request->session()->getId())
            ->update(['last_activity' => now()]);

        $user->updateLastActivity();
    }
}
