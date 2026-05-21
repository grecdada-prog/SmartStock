<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre session a expire. Veuillez vous reconnecter.',
                    'redirect' => route('login', ['inactive' => 1]),
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = auth()->user();

        // Vérifier si l'utilisateur a l'un des rôles requis
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // Si aucun rôle ne correspond, rediriger selon le rôle de l'utilisateur
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Acces non autorise.',
            ], 403);
        }

        if ($user->hasRole('super_admin')) {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->hasRole('manager')) {
            return redirect()->route('manager.dashboard');
        } elseif ($user->hasRole('seller')) {
            return redirect()->route('seller.dashboard');
        }

        abort(403, 'Accès non autorisé');
    }
}
