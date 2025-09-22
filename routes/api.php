<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\PermissionApiController;
use App\Http\Controllers\Api\UserApiController;

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
