<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Landing page - serve static HTML file directly
Route::get('/', function () {
    $path = public_path('index.html');
    if (file_exists($path)) {
        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/html',
        ]);
    }
    return response()->json([
        'message' => 'Welcome to CDP Empire API',
        'status' => 'online',
        'version' => '1.0.0',
    ]);
});

// Comprehensive health check
Route::get('/health', function () {
    $currentDateTime = now();
    $status = [
        'status' => 'healthy',
        'date' => $currentDateTime->toDateString(),
        'time' => $currentDateTime->toTimeString(),
        'service' => 'CDP Empire System API',
        'components' => []
    ];

    // Check database
    try {
        DB::select('SELECT 1');
        $status['components']['database'] = 'healthy';
    } catch (\Exception $e) {
        $status['components']['database'] = 'unhealthy';
        $status['status'] = 'degraded';
    }

    return response()->json($status);
});
