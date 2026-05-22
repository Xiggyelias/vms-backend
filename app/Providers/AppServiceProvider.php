<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use App\Models\Applicant;
use App\Models\Admin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services
        $this->app->singleton(\App\Services\AuthenticationService::class);
        $this->app->singleton(\App\Services\VehicleService::class);
        $this->app->singleton(\App\Repositories\VehicleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for older MySQL versions
        Schema::defaultStringLength(191);

        // Enable strict mode for models in development
        if ($this->app->environment('local')) {
            Model::shouldBeStrict();
        }

        // Prevent lazy loading in production
        if ($this->app->environment('production')) {
            Model::preventLazyLoading();
        }

        // Configure rate limiting for authentication
        $this->configureRateLimiting();

        // Share common data with views
        $this->shareCommonViewData();
    }

    /**
     * Configure rate limiting.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response('Too many login attempts. Please try again in one minute.', 429, $headers);
                });
        });

        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response('Too many admin login attempts. Please try again in one minute.', 429, $headers);
                });
        });
    }

    /**
     * Share common data with views.
     */
    protected function shareCommonViewData(): void
    {
        View::composer('*', function ($view) {
            $view->with('app_name', config('app.name', 'Vehicle Registration System'));
            $view->with('app_version', config('app.version', '1.0.0'));
        });

        View::composer(['auth.*'], function ($view) {
            $view->with('login_attempts_remaining', RateLimiter::remaining('login', 5));
        });
    }
}
