<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Award;
use Illuminate\Http\Request;

class PublicAwardController extends Controller
{
    /**
     * Get active awards listing.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Award::with(['awardType', 'galleries'])
                ->active();

            if ($request->has('award_type_id')) {
                $query->where('award_type_id', $request->award_type_id);
            }

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $query->orderBy('created_at', 'desc');
            $awards = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Awards retrieved successfully',
                'data' => $awards
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve awards',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display a single active award by id or slug.
     */
    public function show(string $idOrSlug)
    {
        try {
            $award = Award::with(['awardType', 'galleries'])
                ->where(function ($query) use ($idOrSlug) {
                    $query->where('id', $idOrSlug)
                        ->orWhere('slug', $idOrSlug);
                })
                ->active()
                ->first();

            if (!$award) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Award not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Award retrieved successfully',
                'data' => $award
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve award',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
