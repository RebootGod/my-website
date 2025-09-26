<?php
// routes/web.php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\MoviePlayerController;
use App\Http\Controllers\SeriesPlayerController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminMovieController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\InviteCodeController;
use App\Http\Controllers\Admin\TMDBController;
use App\Http\Controllers\Admin\NewTMDBController;
use App\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckPermission;

/*
|--------------------------------------------------------------------------
| Public Routes - No Authentication Required
|--------------------------------------------------------------------------
*/

// Home page with integrated filters & search
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search/suggestions', [HomeController::class, 'searchSuggestions'])->name('search.suggestions');
Route::post('/clear-filters', [HomeController::class, 'clearFilters'])->name('filters.clear');

// Movie detail page (public access)
Route::get('/movie/{movie:slug}', [MovieController::class, 'show'])->name('movies.show');

// Series routes (public access)
Route::get('/series/{series}', [\App\Http\Controllers\SeriesController::class, 'show'])->name('series.show');
Route::get('/series', [\App\Http\Controllers\SeriesController::class, 'index'])->name('series.index');

// Genre browsing
Route::get('/genre/{genre:slug}', [MovieController::class, 'byGenre'])->name('movies.genre');

// AJAX endpoints for dynamic content
Route::prefix('api')->group(function () {
    Route::get('/movies/trending', [MovieController::class, 'trending'])->name('api.movies.trending');
    Route::get('/movies/popular', [MovieController::class, 'popular'])->name('api.movies.popular');
    Route::get('/movies/new-releases', [MovieController::class, 'newReleases'])->name('api.movies.new');
});

// Test routes removed for production security

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Login Routes - Rate Limited for Security
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:15,5'); // 15 attempts per 5 minutes
    
    // Registration Routes - Rate Limited for Security  
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('throttle:10,1'); // 10 attempts per minute
    
    // Invite Code Validation - Rate Limited
    Route::get('/check-invite-code', [RegisterController::class, 'checkInviteCode'])
        ->name('invite.check')
        ->middleware('throttle:10,1'); // 10 checks per minute for live validation

    // Password Reset Routes - Rate Limited for Security
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->name('password.email')
        ->middleware('throttle:10,10'); // 10 attempts per 10 minutes (more flexible for testing)
    Route::post('/password/rate-limit-status', [ForgotPasswordController::class, 'getRateLimitStatus'])
        ->name('password.rate-limit-status')
        ->middleware('throttle:20,1'); // 20 checks per minute

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->name('password.update')
        ->middleware('throttle:30,60'); // 30 attempts per hour
    Route::post('/password/validate-token', [ResetPasswordController::class, 'validateToken'])
        ->name('password.validate-token')
        ->middleware('throttle:30,1'); // 30 checks per minute
    Route::post('/password/strength', [ResetPasswordController::class, 'checkPasswordStrength'])
        ->name('password.strength')
        ->middleware('throttle:50,1'); // 50 checks per minute
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'check.user.status', 'password.rehash'])->group(function () {

    // Movie Player (Members Only)
    Route::get('/movie/{movie:slug}/play', [MoviePlayerController::class, 'play'])
        ->name('movies.player');

    // Movie Player with specific source
    Route::get('/movie/{movie}/player/source/{source}', [MoviePlayerController::class, 'play'])
        ->name('movies.player.source');

    // Alternative route for movie player with slug
    Route::get('/movie/{movie:slug}/watch', [MoviePlayerController::class, 'play'])
        ->name('movies.play');

    // Movie view tracking (AJAX endpoint)
    Route::post('/movie/{movie}/track-view', [MovieController::class, 'trackView'])
        ->name('movies.track-view');

    // Report movie issues
    Route::post('/movie/{movie}/report', [MovieController::class, 'reportIssue'])
        ->name('movies.report');
    
    // Get movie sources for player
    Route::get('/movie/{movie}/sources', [MoviePlayerController::class, 'getSources'])
        ->name('movies.sources');

    // Series Episode Player (Members Only)
    Route::get('/series/{series}/episode/{episode}/watch', [SeriesPlayerController::class, 'playEpisode'])
        ->name('series.episode.watch');

    // Get episode info for player
    Route::get('/series/{series}/episode/{episode}/info', [SeriesPlayerController::class, 'getEpisodeInfo'])
        ->name('series.episode.info');

    // Episode view tracking (AJAX endpoint)
    Route::post('/series/{series}/episode/{episode}/track-view', [SeriesPlayerController::class, 'trackEpisodeView'])
        ->name('series.episode.track-view');

    // Report broken link - rate limited to prevent spam
    Route::post('/movie/{movie}/report', [ReportsController::class, 'store'])
        ->name('movies.report')
        ->middleware('throttle:5,1');

    // Report broken episode - rate limited to prevent spam
    Route::post('/series/{series}/episodes/{episode}/report', [ReportsController::class, 'storeEpisodeReport'])
        ->name('series.episode.report')
        ->middleware('throttle:5,60'); // 5 reports per hour
    
    // User Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
        
        // Profile-specific routes
        Route::get('/watchlist', [ProfileController::class, 'watchlist'])->name('watchlist');
        
        // Profile update routes - rate limited for security
        Route::patch('/username', [ProfileController::class, 'updateUsername'])
            ->name('update.username')
            ->middleware('throttle:5,1'); // 5 username changes per minute
        Route::patch('/email', [ProfileController::class, 'updateEmail'])
            ->name('update.email')
            ->middleware('throttle:3,1'); // 3 email changes per minute
        Route::patch('/password', [ProfileController::class, 'updatePassword'])
            ->name('update.password')
            ->middleware('throttle:3,1'); // 3 password changes per minute

        // Account deletion route - rate limited for security
        Route::delete('/delete', [ProfileController::class, 'deleteAccount'])
            ->name('delete')
            ->middleware('throttle:3,1'); // 3 deletion attempts per minute
    });
    
    // Watchlist (moved inside auth middleware group)
    Route::prefix('watchlist')->name('watchlist.')->group(function () {
        Route::get('/', [WatchlistController::class, 'index'])->name('index');
        Route::post('/add/{movie}', [WatchlistController::class, 'add'])
            ->name('add')
            ->middleware('throttle:30,1'); // 30 watchlist additions per minute
        Route::delete('/remove/{movieId}', [WatchlistController::class, 'remove'])
            ->name('remove')
            ->middleware('throttle:30,1'); // 30 watchlist removals per minute
        Route::get('/check/{movie}', [WatchlistController::class, 'check'])->name('check');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes - Rate Limited for Security
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin', CheckPermission::class . ':access_admin_panel', 'throttle:60,1', 'password.rehash', 'audit'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard & Analytics
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
    Route::get('/analytics/realtime', [AnalyticsController::class, 'realtime'])->name('analytics.realtime');
    
    // TMDB Integration - New Version (Movies)
    Route::prefix('tmdb-new')->name('tmdb-new.')->group(function () {
        Route::get('/', [NewTMDBController::class, 'index'])->name('index');
        Route::get('/search', [NewTMDBController::class, 'search'])->name('search');
        Route::get('/popular', [NewTMDBController::class, 'popular'])->name('popular');
        Route::get('/trending', [NewTMDBController::class, 'trending'])->name('trending');
        Route::get('/movie/{tmdb_id}', [NewTMDBController::class, 'getDetails'])->name('details');
        Route::post('/import', [NewTMDBController::class, 'import'])->name('import');
    });

    // TMDB Integration - New Version (Series)
    Route::prefix('series/tmdb-new')->name('series.tmdb-new.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\NewTMDBSeriesController::class, 'index'])->name('index');
        Route::get('/search', [\App\Http\Controllers\Admin\NewTMDBSeriesController::class, 'search'])->name('search');
        Route::get('/popular', [\App\Http\Controllers\Admin\NewTMDBSeriesController::class, 'popular'])->name('popular');
        Route::get('/trending', [\App\Http\Controllers\Admin\NewTMDBSeriesController::class, 'trending'])->name('trending');
        Route::get('/series/{tmdb_id}', [\App\Http\Controllers\Admin\NewTMDBSeriesController::class, 'getDetails'])->name('details');
        Route::post('/import', [\App\Http\Controllers\Admin\NewTMDBSeriesController::class, 'import'])->name('import');
    });

    // TMDB Integration - Old Version (keep for backup)
    Route::prefix('tmdb')->name('tmdb.')->group(function () {
        Route::get('/', [TMDBController::class, 'index'])->name('index');
        Route::get('/search', [TMDBController::class, 'search'])->name('search');
        Route::get('/popular', [TMDBController::class, 'popular'])->name('popular');
        Route::get('/trending', [TMDBController::class, 'trending'])->name('trending');
        Route::get('/movie/{tmdb_id}', [TMDBController::class, 'getDetails'])->name('details');
        Route::post('/import', [TMDBController::class, 'import'])->name('import');
        Route::post('/bulk-import', [TMDBController::class, 'bulkImport'])->name('bulk-import');
        Route::post('/sync/{movie}', [TMDBController::class, 'syncMovie'])->name('sync');
    });
    
    // Movie Management - Refactored to use AdminMovieController
    Route::prefix('movies')->name('movies.')->group(function () {
        Route::get('/', [AdminMovieController::class, 'index'])->name('index');
        Route::get('/create', [AdminMovieController::class, 'create'])->name('create');
        Route::post('/store', [AdminMovieController::class, 'store'])->name('store');
        Route::get('/tmdb', [TMDBController::class, 'index'])->name('tmdb');
        Route::get('/{movie}', [AdminMovieController::class, 'show'])->name('show');
        Route::get('/{movie}/edit', [AdminMovieController::class, 'edit'])->name('edit');
        Route::put('/{movie}', [AdminMovieController::class, 'update'])->name('update');
        Route::delete('/{movie}', [AdminMovieController::class, 'destroy'])->name('destroy');
        
        // Toggle movie status
        Route::patch('/{movie}/toggle-status', [AdminMovieController::class, 'toggleStatus'])->name('toggle-status');
        // TMDB Integration routes
        Route::get('/tmdb/search', [AdminMovieController::class, 'tmdbSearch'])->name('tmdb.search');
        Route::get('/tmdb/details', [AdminMovieController::class, 'tmdbDetails'])->name('tmdb.details');
        Route::post('/tmdb/import', [AdminMovieController::class, 'tmdbImport'])->name('tmdb.import');
        Route::post('/tmdb/bulk-import', [AdminMovieController::class, 'tmdbBulkImport'])->name('tmdb.bulk-import');
        
        // Movie Sources Management
        Route::prefix('{movie}/sources')->name('sources.')->group(function () {
            Route::get('/', [AdminMovieController::class, 'sources'])->name('index');
            Route::post('/', [AdminMovieController::class, 'storeSource'])->name('store');
            Route::put('/{source}', [AdminMovieController::class, 'updateSource'])->name('update');
            Route::delete('/{source}', [AdminMovieController::class, 'destroySource'])->name('destroy');
            Route::post('/{source}/toggle', [AdminMovieController::class, 'toggleSource'])->name('toggle');
            Route::post('/{source}/reset-reports', [AdminMovieController::class, 'resetReports'])->name('reset-reports');
            Route::post('/migrate', [AdminMovieController::class, 'migrateSource'])->name('migrate');
        });
    });
    
        // Series Management
        Route::prefix('series')->name('series.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'store'])->name('store');
            Route::get('/tmdb-import', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'showTmdbImport'])->name('tmdb-import');
            Route::post('/tmdb-import', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'tmdbImport'])->name('tmdb-import.store');
            Route::post('/tmdb-bulk-import', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'tmdbBulkImport'])->name('tmdb-bulk-import');
            Route::get('/tmdb-search', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'tmdbSearch'])->name('tmdb-search');
            Route::get('/{series}', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'show'])->name('show');
            Route::get('/{series}/edit', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'edit'])->name('edit');
            Route::put('/{series}', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'update'])->name('update');
            Route::delete('/{series}', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'destroy'])->name('destroy');
            Route::post('/{series}/toggle-status', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'toggleStatus'])->name('toggle-status');

            // Season and Episode management
            Route::post('/{series}/seasons', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'storeSeason'])->name('seasons.store');
            Route::delete('/{series}/seasons/{season}', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'destroySeason'])->name('seasons.destroy');
            Route::post('/{series}/episodes', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'storeEpisode'])->name('episodes.store');
            Route::delete('/{series}/episodes/{episode}', [\App\Http\Controllers\Admin\AdminSeriesController::class, 'destroyEpisode'])->name('episodes.destroy');
        });
    
    // Broken Link Reports Management  
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [AdminMovieController::class, 'reports'])->name('index');
        Route::put('/{report}', [AdminMovieController::class, 'updateReport'])->name('update');
    });
    
    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/export', [UserManagementController::class, 'export'])->name('export');
        Route::get('/generate-password', [UserManagementController::class, 'generatePassword'])->name('generate-password');
        // Route::get('/create', [UserManagementController::class, 'create'])->name('create'); // DISABLED FOR SECURITY
        // Route::post('/store', [UserManagementController::class, 'store'])->name('store'); // DISABLED FOR SECURITY
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');

        // User actions
        Route::post('/{user}/toggle-status', [UserManagementController::class, 'toggleBan'])->name('toggle-status');
        Route::post('/{user}/toggle-ban', [UserManagementController::class, 'toggleBan'])->name('toggle-ban');
        Route::post('/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
        Route::post('/bulk-action', [UserManagementController::class, 'bulkAction'])->name('bulk-action');
    });

    // User Activity Management
    Route::prefix('user-activity')->name('user-activity.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserActivityController::class, 'index'])->name('index');
        Route::get('/stats', [\App\Http\Controllers\Admin\UserActivityController::class, 'getStats'])->name('stats');
        Route::get('/export', [\App\Http\Controllers\Admin\UserActivityController::class, 'export'])->name('export');
        Route::post('/clear-cache', [\App\Http\Controllers\Admin\UserActivityController::class, 'clearCache'])->name('clear-cache');
        Route::post('/cleanup', [\App\Http\Controllers\Admin\UserActivityController::class, 'cleanup'])->name('cleanup');
        Route::get('/{user}', [\App\Http\Controllers\Admin\UserActivityController::class, 'show'])->name('show');
    });
    
    // Role & Permission Management
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });
    
    // Invite Code Management
    Route::prefix('invite-codes')->name('invite-codes.')->group(function () {
        Route::get('/', [InviteCodeController::class, 'index'])->name('index');
        Route::get('/create', [InviteCodeController::class, 'create'])->name('create');
        Route::post('/store', [InviteCodeController::class, 'store'])->name('store');
        Route::post('/generate', [InviteCodeController::class, 'generate'])->name('generate');
        Route::post('/{code}/toggle-status', [InviteCodeController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{code}', [InviteCodeController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-generate', [InviteCodeController::class, 'bulkGenerate'])->name('bulk-generate');
    });
    
    // Reports Management
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('/{report}', [ReportsController::class, 'show'])->name('show');
        Route::put('/{report}', [ReportsController::class, 'update'])->name('update');
        Route::delete('/{report}', [ReportsController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-update', [ReportsController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/statistics', [ReportsController::class, 'statistics'])->name('statistics');
    });
    
    // Admin Activity Logs & Audit Logs
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminLogController::class, 'index'])->name('index');
        Route::get('/export', [App\Http\Controllers\Admin\AdminLogController::class, 'export'])->name('export');
        Route::get('/{log}', [App\Http\Controllers\Admin\AdminLogController::class, 'show'])->name('show');
    });

    // Audit Logs (Security & Actions)
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('index');
        Route::get('/security', [App\Http\Controllers\Admin\AuditLogController::class, 'security'])->name('security');
        Route::get('/export', [App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('export');
        Route::get('/{auditLog}', [App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('show');
    });
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return redirect()->route('home')->with('error', 'Page not found');
});