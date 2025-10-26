<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\PermissionApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\Bot\BotMovieController;
use App\Http\Controllers\Api\Bot\BotSeriesController;
use App\Http\Controllers\Api\Bot\BotSeasonController;
use App\Http\Controllers\Api\Bot\BotEpisodeController;
use App\Http\Controllers\Api\Bot\BotEpisodeStatusController;
use App\Http\Controllers\Api\Bot\BotEpisodeUpdateController;

Route::middleware(['auth:sanctum', 'check.permission:access_admin_panel'])->prefix('admin')->group(function () {
    Route::get('/roles', [RoleApiController::class, 'index']);
    Route::post('/roles', [RoleApiController::class, 'store']);
    Route::put('/roles/{role}', [RoleApiController::class, 'update']);
    Route::delete('/roles/{role}', [RoleApiController::class, 'destroy']);

    Route::get('/permissions', [PermissionApiController::class, 'index']);
    Route::post('/permissions', [PermissionApiController::class, 'store']);
    Route::put('/permissions/{permission}', [PermissionApiController::class, 'update']);
    Route::delete('/permissions/{permission}', [PermissionApiController::class, 'destroy']);

    Route::get('/users', [UserApiController::class, 'index']);
    Route::put('/users/{user}/role', [UserApiController::class, 'updateRole']);
    Route::put('/users/{user}/permissions', [UserApiController::class, 'updatePermissions']);
});

// Telegram Bot API Routes
Route::middleware(['auth.bot', 'throttle:100,1'])->prefix('bot')->group(function () {
    // Movie upload
    Route::post('/movies', [BotMovieController::class, 'store']);
    
    // Series upload (creates series only, no seasons/episodes)
    Route::post('/series', [BotSeriesController::class, 'store']);
    
    // Season upload (creates season only, no episodes)
    Route::post('/series/{tmdbId}/seasons', [BotSeasonController::class, 'store']);
    
    // Episode upload (creates individual episode with URLs)
    Route::post('/series/{tmdbId}/episodes', [BotEpisodeController::class, 'store']);
    
    // Get episode status for a season (check which episodes exist and need URLs)
    Route::get('/series/{tmdbId}/episodes-status', [BotEpisodeStatusController::class, 'getStatus']);
    
    // Update episode URLs (for episodes that exist but have no URLs)
    Route::put('/episodes/{episodeId}', [BotEpisodeUpdateController::class, 'update']);
});
