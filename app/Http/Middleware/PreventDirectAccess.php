<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDirectAccess
{
    /**
     * Routes publiques autorisées (accès libre)
     */
    protected $publicRoutes = [
        '/',
        'login',
        'superadmin.login',
        'login.post',
        'superadmin.login.post',
        '2fa.verify',
        '2fa.verify.post',
    ];

    /**
     * Chemins publics autorisés
     */
    protected $publicPaths = [
        '/',
        '/login',
        '/superadmin/login',
        '/2fa/verify',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $currentRoute = $request->route() ? $request->route()->getName() : null;
        $currentPath = $request->path();

        // Autoriser les routes publiques
        if (in_array($currentRoute, $this->publicRoutes) || 
            in_array('/' . $currentPath, $this->publicPaths) ||
            $currentPath === '/') {
            return $next($request);
        }

        // Autoriser les assets (CSS, JS, images)
        if ($request->is('build/*') || 
            $request->is('storage/*') || 
            $request->is('css/*') || 
            $request->is('js/*') || 
            $request->is('images/*')) {
            return $next($request);
        }

        // Pour toutes les autres routes, vérifier l'authentification
        if (!auth()->check()) {
            abort(403, 'Accès non autorisé. Veuillez vous connecter via l\'interface appropriée.');
        }

        // Vérifier que l'utilisateur accède depuis l'application
        $referer = $request->headers->get('referer');
        $appUrl = config('app.url');

        // Autoriser les requêtes AJAX et API
        if ($request->ajax() || $request->wantsJson()) {
            return $next($request);
        }

        // Si c'est juste après le login, autoriser
        if (session()->has('just_logged_in')) {
            session()->forget('just_logged_in');
            session(['navigation_allowed' => true]);
            return $next($request);
        }

        // Si la navigation est déjà autorisée dans cette session
        if (session()->has('navigation_allowed')) {
            return $next($request);
        }

        // Si pas de referer, vérifier si c'est la première page après login
        if (empty($referer)) {
            // Rediriger vers le dashboard approprié selon le rôle
            $user = auth()->user();
            
            if ($user->hasRole('super_admin')) {
                // Si déjà sur le dashboard super admin, autoriser
                if ($request->is('superadmin/dashboard')) {
                    session(['navigation_allowed' => true]);
                    return $next($request);
                }
                return redirect()->route('superadmin.dashboard');
            } elseif ($user->hasRole('manager')) {
                if ($request->is('manager/dashboard')) {
                    session(['navigation_allowed' => true]);
                    return $next($request);
                }
                return redirect()->route('manager.dashboard');
            } elseif ($user->hasRole('seller')) {
                if ($request->is('seller/dashboard')) {
                    session(['navigation_allowed' => true]);
                    return $next($request);
                }
                return redirect()->route('seller.dashboard');
            }
        }

        // Vérifier que le referer vient bien de l'application
        if (!empty($referer) && str_starts_with($referer, $appUrl)) {
            session(['navigation_allowed' => true]);
            return $next($request);
        }

        // Si aucune condition n'est remplie, bloquer
        abort(403, 'Accès direct par URL non autorisé. Utilisez la navigation de l\'application.');
    }
}