<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Admin\MovieTMDBService;
use App\Services\Admin\MovieSourceService;
use App\Services\Admin\MovieFileService;
use App\Services\Admin\MovieReportService;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MovieTMDBService::class, function ($app) {
            return new MovieTMDBService();
        });

        $this->app->singleton(MovieSourceService::class, function ($app) {
            return new MovieSourceService();
        });

        $this->app->singleton(MovieFileService::class, function ($app) {
            return new MovieFileService();
        });

        $this->app->singleton(MovieReportService::class, function ($app) {
            return new MovieReportService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}