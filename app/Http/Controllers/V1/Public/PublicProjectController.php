<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class PublicProjectController extends Controller
{
    /**
     * Get active projects listing.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Project::active();

            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->has('location') && $request->location != '') {
                $query->where('location', 'LIKE', "%{$request->location}%");
            }

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $query->orderBy('created_at', 'desc');
            $projects = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Projects retrieved successfully',
                'data' => $projects
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve projects',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display a single active project by id or slug.
     */
    public function show(string $idOrSlug)
    {
        try {
            $project = Project::where(function ($query) use ($idOrSlug) {
                    $query->where('id', $idOrSlug)
                        ->orWhere('slug', $idOrSlug);
                })
                ->active()
                ->first();

            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Project retrieved successfully',
                'data' => $project
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve project',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
