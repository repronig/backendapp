<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(3)->by((string) $request->input('email')),
            ];
        });

        RateLimiter::for('email-verification', function (Request $request) {
            return [
                Limit::perMinute(6)->by((string) optional($request->user())->id ?: $request->ip()),
            ];
        });

        /* $this->routes(function () {
            require base_path('routes/api.php');
        }); */
    }
}