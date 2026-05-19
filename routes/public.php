<?php

use App\Http\Controllers\V1\Public\PublicCMSController;
use App\Http\Controllers\V1\Public\PublicBranchController;
use App\Http\Controllers\V1\Public\PublicCareerController;
use App\Http\Controllers\V1\Public\PublicContactTypeController;
use App\Http\Controllers\V1\Public\PublicContactController;
use App\Http\Controllers\V1\Public\PublicEventController;
use App\Http\Controllers\V1\Public\PublicPlanController;
use App\Http\Controllers\V1\Public\PublicServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/public')->group(function () {
    Route::get('cms/{page}', [PublicCMSController::class, 'getPageContent']);

    Route::get('branches', [PublicBranchController::class, 'index']);

    Route::get('careers', [PublicCareerController::class, 'index']);
    Route::get('careers/{idOrSlug}', [PublicCareerController::class, 'show']);
    Route::post('careers/apply', [PublicCareerController::class, 'apply']);

    Route::get('contact-types', [PublicContactTypeController::class, 'index']);
    Route::post('contacts', [PublicContactController::class, 'store']);

    Route::get('services', [PublicServiceController::class, 'index']);

    Route::get('events', [PublicEventController::class, 'index']);
    Route::get('events/{idOrSlug}', [PublicEventController::class, 'show']);

    Route::get('plans', [PublicPlanController::class, 'index']);
});
