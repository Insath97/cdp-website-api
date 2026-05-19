<?php

use App\Http\Controllers\V1\Public\PublicCMSController;
use App\Http\Controllers\V1\Public\PublicBranchController;
<<<<<<< HEAD
use App\Http\Controllers\V1\Public\PublicCareerController;
=======
use App\Http\Controllers\v1\Public\PublicContactTypeController;
use App\Http\Controllers\V1\Public\PublicContactController;
use App\Http\Controllers\V1\Public\PublicEventController;
use App\Http\Controllers\V1\Public\PublicPlanController;
use App\Http\Controllers\V1\Public\PublicServiceController;
>>>>>>> 0fdb880 (Added contact module)
use Illuminate\Support\Facades\Route;

Route::prefix('v1/public')->group(function () {
    Route::get('cms/{page}', [PublicCMSController::class, 'getPageContent']);
    Route::get('branches', [PublicBranchController::class, 'index']);
<<<<<<< HEAD
    Route::get('careers', [PublicCareerController::class, 'index']);
    Route::get('careers/{idOrSlug}', [PublicCareerController::class, 'show']);
=======
    Route::get('contact-types', [PublicContactTypeController::class, 'index']);
    Route::post('contacts', [PublicContactController::class, 'store']);
    Route::get('services', [PublicServiceController::class, 'index']);
    Route::get('events', [PublicEventController::class, 'index']);
    Route::get('plans', [PublicPlanController::class, 'index']);
>>>>>>> 0fdb880 (Added contact module)
});
