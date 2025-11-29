<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDirectNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si c'est une requête AJAX ou API, laisser passer
        if ($request->ajax() || $request->wantsJson()) {
            return $next($request);
        }

        // Si la session indique que la navigation est autorisée
        if (session()->has('navigation_allowed')) {
            return $next($request);
        }

        // Si c'est juste après le login
        if (session()->has('just_logged_in')) {
            session()->forget('just_logged_in');
            session(['navigation_allowed' => true]);
            return $next($request);
        }

        // Vérifier le referer
        $referer = $request->headers->get('referer');
        $appUrl = config('app.url');

        // Si le referer vient de l'application, autoriser
        if (!empty($referer) && str_starts_with($referer, $appUrl)) {
            session(['navigation_allowed' => true]);
            return $next($request);
        }

        // Cas spécial : première visite du dashboard après login
        $user = auth()->user();
        $currentPath = $request->path();

        if ($user->hasRole('super_admin') && $currentPath === 'superadmin/dashboard') {
            session(['navigation_allowed' => true]);
            return $next($request);
        } elseif ($user->hasRole('manager') && $currentPath === 'manager/dashboard') {
            session(['navigation_allowed' => true]);
            return $next($request);
        } elseif ($user->hasRole('seller') && $currentPath === 'seller/dashboard') {
            session(['navigation_allowed' => true]);
            return $next($request);
        }

        // Sinon, bloquer
        abort(403, 'Accès direct par URL non autorisé. Utilisez la navigation de l\'application.');
    }
}