<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        RateLimiter::for('checks', fn (Request $request) => Limit::perMinute(config('sayno.web_rate_limit'))
            ->by($request->ip())
            ->response(fn () => back()->withInput()->withErrors(['throttle' => __('web.errors.too_many')])));

        RateLimiter::for('org-login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('cron', fn (Request $request) => Limit::perMinute(4)->by($request->ip()));
    }
}
