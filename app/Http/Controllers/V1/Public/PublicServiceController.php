<?php

namespace App\Http\Controllers\v1\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class PublicServiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $services = Service::active()
            ->orderBy('created_at', 'desc')
            ->get(['id','imagepath','title', 'description']);

            return response()->json([
                'status' => 'success',
                'message' => $services->isEmpty()
                    ? "No services found."
                    : "Services retrieved successfully",
            'data' => $services
        ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve services',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
