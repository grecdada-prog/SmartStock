<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Vérifier si le compte est actif
            if (!$user->is_active) {
                Log::warning('Utilisateur avec compte désactivé tenté de naviguer', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Votre compte a été désactivé.');
            }

            // Bloquer l'accès direct au /dashboard et rediriger selon le rôle
            if ($request->is('dashboard')) {
                if ($user->isGerant()) {
                    return redirect()->route('admin.dashboard');
                } elseif ($user->isVendeur()) {
                    return redirect()->route('pos.dashboard');
                }
            }

            // Bloquer les vendeurs d'accéder aux routes admin
            if ($request->is('admin/*') && !$user->isGerant()) {
                Log::alert('SÉCURITÉ: Vendeur tenté d\'accéder à une zone admin', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'url' => $request->fullUrl(),
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Accès non autorisé détecté.');
            }

            // Bloquer les gérants d'accéder aux routes POS (optionnel)
            if ($request->is('pos/*') && !$user->isVendeur()) {
                return redirect()->route('admin.dashboard');
            }
        }

        return $next($request);
    }
}