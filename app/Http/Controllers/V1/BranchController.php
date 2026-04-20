<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Branch Index', only: ['index', 'show']),
            new Middleware('permission:Branch Create', only: ['store']),
            new Middleware('permission:Branch Update', only: ['update', 'activate', 'deactivate']),
            new Middleware('permission:Branch Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Branch::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('city')) {
                $query->where('city', $request->city);
            }

            $query->orderBy('created_at', 'desc');
            $branches = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Branches retrieved successfully',
                'data' => $branches
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve branches',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBranchRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $branch = Branch::create($data);

            DB::commit();

            $this->logActivity('CREATE', 'Branch', "Created branch: {$branch->name} ({$branch->code})");

            return response()->json([
                'status' => 'success',
                'message' => 'Branch created successfully',
                'data' => $branch
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create branch',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Branch retrieved successfully',
                'data' => $branch
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve branch',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBranchRequest $request, string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $branch->update($data);

            DB::commit();

            $this->logActivity('UPDATE', 'Branch', "Updated branch: {$branch->name}");

            return response()->json([
                'status' => 'success',
                'message' => 'Branch updated successfully',
                'data' => $branch
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update branch',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            $branchName = $branch->name;
            $branch->delete();

            $this->logActivity('DELETE', 'Branch', "Deleted branch: {$branchName}");

            return response()->json([
                'status' => 'success',
                'message' => 'Branch deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete branch',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Activate the branch.
     */
    public function activate(string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                ], 404);
            }

            if ($branch->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch is already active',
                ], 422);
            }

            $branch->update(['is_active' => true]);

            $this->logActivity('ACTIVATE', 'Branch', "Activated branch: {$branch->name}");

            return response()->json([
                'status' => 'success',
                'message' => 'Branch activated successfully',
                'data' => $branch
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate branch',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Deactivate the branch.
     */
    public function deactivate(string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                ], 404);
            }

            if (!$branch->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch is already inactive',
                ], 422);
            }

            $branch->update(['is_active' => false]);

            $this->logActivity('DEACTIVATE', 'Branch', "Deactivated branch: {$branch->name}");

            return response()->json([
                'status' => 'success',
                'message' => 'Branch deactivated successfully',
                'data' => $branch
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate branch',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
