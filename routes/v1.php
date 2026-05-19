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
use App\Http\Controllers\V1\ActivityLogController;
use App\Http\Controllers\V1\CareerController;
use App\Http\Controllers\V1\ContactController;
use App\Http\Controllers\V1\ContactTypeController;


Route::prefix('v1')->middleware('throttle:auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

/* protected routes */
Route::middleware(['auth', 'throttle:api'])->prefix('v1')->group(function () {
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

    Route::apiResource('contact-types', ContactTypeController::class);
    Route::prefix('contact-types')->group(function () {
        Route::patch('{id}/activate', [ContactTypeController::class, 'activate']);
        Route::patch('{id}/deactivate', [ContactTypeController::class, 'deactivate']);
    });

    Route::apiResource('contacts', ContactController::class);
    Route::prefix('contacts')->group(function () {
        Route::patch('{id}/activate', [ContactController::class, 'activate']);
        Route::patch('{id}/deactivate', [ContactController::class, 'deactivate']);
        Route::post('{id}/send-reply-email', [ContactController::class, 'reply']);
    });

    Route::get('events/tags/list', [EventController::class, 'getAvailableTags']);
    Route::apiResource('events', EventController::class);
    Route::prefix('events')->group(function () {
        Route::patch('{id}/toggle-status', [EventController::class, 'toggleStatus']);
        Route::patch('{id}/approve', [EventController::class, 'approve']);
        Route::patch('{id}/reject', [EventController::class, 'reject']);
        Route::patch('{id}/restore', [EventController::class, 'restore']);
        Route::delete('{id}/force-delete', [EventController::class, 'forceDelete']);
    });

    Route::apiResource('careers', CareerController::class);
    Route::prefix('careers')->group(function () {
        Route::patch('{id}/toggle-status', [CareerController::class, 'toggleStatus']);
        Route::patch('{id}/restore', [CareerController::class, 'restore']);
        Route::delete('{id}/force-delete', [CareerController::class, 'forceDelete']);
    });

    Route::apiResource('plans', PlanController::class);
    Route::prefix('plans')->group(function () {
        Route::patch('{id}/activate', [PlanController::class, 'activate']);
        Route::patch('{id}/deactivate', [PlanController::class, 'deactivate']);
    });
});
