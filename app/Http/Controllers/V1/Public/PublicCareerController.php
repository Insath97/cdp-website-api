<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Career;
use Illuminate\Http\Request;

class PublicCareerController extends Controller
{
    /**
     * Display a listing of active and unexpired career postings.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Career::with(['responsibilities', 'requirements', 'benefits'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('due_date')
                      ->orWhere('due_date', '>=', now()->toDateString());
                });

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('department') && $request->department != '') {
                $query->where('department', $request->department);
            }

            if ($request->has('location') && $request->location != '') {
                $query->where('location', $request->location);
            }

            if ($request->has('job_type') && $request->job_type != '') {
                $query->where('job_type', $request->job_type);
            }

            $query->orderBy('created_at', 'desc');
            $careers = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Public career postings retrieved successfully',
                'data' => $careers,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve public career postings',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified active and unexpired career posting.
     */
    public function show(string $idOrSlug)
    {
        try {
            $career = Career::with(['responsibilities', 'requirements', 'benefits'])
                ->where(function ($q) use ($idOrSlug) {
                    $q->where('id', $idOrSlug)
                      ->orWhere('slug', $idOrSlug);
                })
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('due_date')
                      ->orWhere('due_date', '>=', now()->toDateString());
                })
                ->first();

            if (! $career) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career posting not found or has expired',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Career posting retrieved successfully',
                'data' => $career,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve career posting',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
