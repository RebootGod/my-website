<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Movie;
use App\Models\Watchlist;
use App\Policies\UserPolicy;
use App\Policies\MoviePolicy;
use App\Policies\WatchlistPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Movie::class => MoviePolicy::class,
        Watchlist::class => WatchlistPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates if needed
        Gate::define('access-admin-panel', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('super-admin-only', function (User $user) {
            return $user->isSuperAdmin();
        });
    }
}