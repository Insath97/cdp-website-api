<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class PublicBranchController extends Controller
{
    /**
     * Display a listing of active branches for the public.
     */
    public function index(Request $request)
    {
        try {
            $branches = Branch::active()
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'code', 'address', 'city']);

            return response()->json([
                'status' => 'success',
                'message' => 'Public branches retrieved successfully',
                'data' => $branches
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve public branches',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
