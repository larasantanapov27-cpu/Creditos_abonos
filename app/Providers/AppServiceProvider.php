<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::useBootstrapFive();
        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('username')).'|'.$request->ip())
            ->response(fn () => back()->withErrors([
                'username' => 'Demasiados intentos. Espera un minuto antes de intentar de nuevo.',
            ]))
        );
    }
}
