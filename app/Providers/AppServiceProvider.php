<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        //
    }
}
