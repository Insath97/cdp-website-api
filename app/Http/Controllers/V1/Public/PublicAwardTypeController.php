<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AwardType;

class PublicAwardTypeController extends Controller
{
    /**
     * Get active Award Types list.
     */
    public function index(Request $request)
    {
        try {
            $query = AwardType::active()->select('id', 'name', 'slug', 'description');

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $awardTypes = $query->orderBy('name', 'asc')->get();

            return response()->json([
                'status' => 'success',
                'message' => $awardTypes->isEmpty()
                    ? "No award types found."
                    : "Award types retrieved successfully",
                'data' => $awardTypes
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Award Types',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
