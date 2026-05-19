<?php

namespace App\Http\Controllers\v1\Public;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PublicPlanController extends Controller
{
    public function index(Request $request)
    {
        try {
            $plans = Plan::active()
            ->orderBy('created_at', 'desc')
            ->get(['id','image','maintitle', 'subtitle', 'short_description']);

            return response()->json([
                'status' => 'success',
                'message' => $plans->isEmpty()
                    ? "No plans found."
                    : "Plans retrieved successfully",
            'data' => $plans
        ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve plans',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
