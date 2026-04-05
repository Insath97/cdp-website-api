<?php

use App\Http\Controllers\V1\Public\PublicCMSController;

Route::prefix('v1/public')->group(function () {
    Route::get('cms/{page}', [PublicCMSController::class, 'getPageContent']);
});
