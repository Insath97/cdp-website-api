<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CMSController;
use App\Http\Controllers\V1\PermissionController;
use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\BranchController;
use App\Http\Controllers\V1\SettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\ServiceController;
use App\Http\Controllers\V1\PlanController;
use App\Http\Controllers\V1\EventController;
use App\Http\Controllers\V1\Public\PublicEventController;
use App\Http\Controllers\V1\ActivityLogController;


Route::prefix('v1')->middleware('throttle:auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

/* public routes - no auth required */
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('public/events', [PublicEventController::class, 'index']);
    Route::get('public/events/{idOrSlug}', [PublicEventController::class, 'show']);
});

/* protected routes */
Route::middleware(['auth:api', 'throttle:api'])->prefix('v1')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('permissions/list/', [PermissionController::class, 'getAvailablePermissions']);
    Route::apiResource('permissions', PermissionController::class);

    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show']);

    Route::get('roles/list/', [RoleController::class, 'getAvailableRoles']);
    Route::apiResource('roles', RoleController::class);

    Route::apiResource('users', UserController::class);
    Route::prefix('users')->group(function () {
        Route::patch('{id}/activate', [UserController::class, 'activate']);
        Route::patch('{id}/deactivate', [UserController::class, 'deactivate']);
        Route::patch('{id}/profile-image', [UserController::class, 'updateProfileImage']);
        Route::delete('{id}/profile-image', [UserController::class, 'removeProfileImage']);
    });

    Route::prefix('cms')->group(function () {
        Route::get('/', [CMSController::class, 'index']);
        Route::post('update', [CMSController::class, 'update']);
    });

    Route::apiResource('branches', BranchController::class);
    Route::prefix('branches')->group(function () {
        Route::patch('{id}/activate', [BranchController::class, 'activate']);
        Route::patch('{id}/deactivate', [BranchController::class, 'deactivate']);
    });

    Route::get('settings', [SettingController::class, 'index']);
    Route::post('settings', [SettingController::class, 'update']);

    Route::apiResource('services', ServiceController::class);
    Route::prefix('services')->group(function () {
        Route::patch('{id}/toggle-status', [ServiceController::class, 'toggleStatus']);
    });

    Route::apiResource('events', EventController::class);
    Route::prefix('events')->group(function () {
        Route::patch('{id}/toggle-status', [EventController::class, 'toggleStatus']);
        Route::patch('{id}/approve', [EventController::class, 'approve']);
        Route::patch('{id}/reject', [EventController::class, 'reject']);
        Route::patch('{id}/restore', [EventController::class, 'restore']);
        Route::delete('{id}/force-delete', [EventController::class, 'forceDelete']);
    });

});
