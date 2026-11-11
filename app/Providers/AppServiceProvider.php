<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\ServiceProvider;

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
        // Forzar esquema seguro y URLs dinámicas para ngrok y servidores locales
    if (env('APP_ENV') !== 'local' || request()->header('x-forwarded-host')) {
        URL::forceScheme('https');
    }

    }
}
