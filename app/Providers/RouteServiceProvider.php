<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) env('API_RATE_LIMIT_PER_MINUTE', 120))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $identifier = Str::lower((string) ($request->input('identifier') ?: $request->input('username') ?: 'guest'));

            return Limit::perMinutes(
                (int) env('LOGIN_RATE_LIMIT_WINDOW_MINUTES', 15),
                (int) env('LOGIN_RATE_LIMIT_MAX_ATTEMPTS', 5)
            )->by($identifier . '|' . $request->ip());
        });

        RateLimiter::for('oauth', function (Request $request) {
            return Limit::perMinute((int) env('OAUTH_RATE_LIMIT_PER_MINUTE', 30))
                ->by($request->ip());
        });

        RateLimiter::for('mutations', function (Request $request) {
            return Limit::perMinute((int) env('MUTATION_RATE_LIMIT_PER_MINUTE', 120))
                ->by(($request->session()->get('user_id') ?: $request->session()->get('admin_id') ?: $request->ip()));
        });

        RateLimiter::for('search', function (Request $request) {
            // 30 plate lookups per minute per user (or IP if unauthenticated)
            return Limit::perMinute((int) env('SEARCH_RATE_LIMIT_PER_MINUTE', 30))
                ->by($request->session()->get('user_id') ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
