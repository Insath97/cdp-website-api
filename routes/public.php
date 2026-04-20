<?php

use App\Http\Controllers\V1\Public\PublicCMSController;
use App\Http\Controllers\V1\Public\PublicBranchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/public')->group(function () {
    Route::get('cms/{page}', [PublicCMSController::class, 'getPageContent']);
    Route::get('branches', [PublicBranchController::class, 'index']);
});
