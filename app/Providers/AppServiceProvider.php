<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer les événements et listeners
        Event::listen(
            UserLoggedIn::class,
            LogSuccessfulLogin::class,
        );

        Event::listen(
            UserLoggedOut::class,
            LogSuccessfulLogout::class,
        );

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
