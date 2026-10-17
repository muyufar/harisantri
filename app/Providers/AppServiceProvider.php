<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Illuminate\Support\Carbon::setLocale(config('app.locale'));
        View::share('currentEvent', null);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('lookup', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('upload', fn (Request $request) => Limit::perMinute(8)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }
}
