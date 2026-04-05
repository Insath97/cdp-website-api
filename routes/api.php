<?php

use Illuminate\Support\Facades\Route;

// Health Check endpoint
Route::get('/health-check', function () {
    return response()->json(['message' => 'CDP Empire API is working!']);
});

require __DIR__ . '/v1.php';

/* public routes */
require __DIR__ . '/public.php';
