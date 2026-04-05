<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\PermissionController;
use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

/* protected routes */
Route::middleware(['auth:api', 'throttle:api'])->prefix('v1')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('permissions/list/', [PermissionController::class, 'getAvailablePermissions']);
    Route::apiResource('permissions', PermissionController::class);

    Route::get('roles/list/', [RoleController::class, 'getAvailableRoles']);
    Route::apiResource('roles', RoleController::class);

    Route::apiResource('users', UserController::class);
    Route::prefix('users')->group(function () {
        Route::patch('{id}/activate', [UserController::class, 'activate']);
        Route::patch('{id}/deactivate', [UserController::class, 'deactivate']);
        Route::patch('{id}/profile-image', [UserController::class, 'updateProfileImage']);
        Route::delete('{id}/profile-image', [UserController::class, 'removeProfileImage']);
    });
});
