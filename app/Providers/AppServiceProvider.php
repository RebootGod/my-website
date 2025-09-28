<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\RunSecurityTests;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TMDB Services
        $this->app->singleton(\App\Services\NewTMDBService::class, function ($app) {
            return new \App\Services\NewTMDBService();
        });
        
        $this->app->singleton(\App\Services\TMDBService::class, function ($app) {
            return new \App\Services\TMDBService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RunSecurityTests::class,
            ]);
        }
    }
}
