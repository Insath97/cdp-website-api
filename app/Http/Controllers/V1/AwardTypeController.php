<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AwardType;
use App\Http\Requests\CreateAwardTypeRequest;
use App\Http\Requests\UpdateAwardTypeRequest;
use Illuminate\Support\Facades\DB;
use App\Traits\ActivityLogTrait;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AwardTypeController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Award Type Index', only: ['index', 'show']),
            new Middleware('permission:Award Type Create', only: ['store']),
            new Middleware('permission:Award Type Update', only: ['update']),
            new Middleware('permission:Award Type Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Award Type Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = AwardType::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $query->orderBy('created_at', 'desc');
            $award_types = $query->paginate($perPage);

            $this->logActivity('INDEX', 'Award Type', "Viewed award types list");

            return response()->json([
                'status' => true,
                'message' => 'Award types fetched successfully',
                'data' => $award_types
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => null
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateAwardTypeRequest $request)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();

            if (empty($validatedData['slug']) && !empty($validatedData['name'])) {
                $validatedData['slug'] = \Illuminate\Support\Str::slug($validatedData['name']);
            }

            $award_type = AwardType::create($validatedData);

            DB::commit();

            $this->logActivity('CREATE', 'Award Type', "Created award type: {$award_type->name}");

            return response()->json([
                'status' => true,
                'message' => 'Award type created successfully',
                'data' => $award_type
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create award type',
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
            $award_type = AwardType::query()->find($id);

            if (!$award_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Award type not found',
                    'data' => null
                ], 404);
            }

            $this->logActivity('SHOW', 'Award Type', "Viewed award type details: {$award_type->name}", ['award_type_id' => $award_type->id]);

            return response()->json([
                'status' => true,
                'message' => 'Award type fetched successfully',
                'data' => $award_type
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => null
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAwardTypeRequest $request, string $id)
    {
        try {
            $award_type = AwardType::query()->find($id);

            if (!$award_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Award type not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->all();

            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
            }

            $award_type->update($data);

            DB::commit();

            $this->logActivity('UPDATE', 'Award Type', "Updated award type: {$award_type->name}");

            return response()->json([
                'status' => true,
                'message' => 'Award type updated successfully',
                'data' => $award_type
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => null
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $award_type = AwardType::find($id);

            if (!$award_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Award type not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $awardTypeName = $award_type->name;
            $award_type->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Award Type', "Deleted award type: {$awardTypeName}");

            return response()->json([
                'status' => true,
                'message' => 'Award type deleted successfully',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => null
            ], 500);
        }
    }

    /**
     * Get lightweight active award types list (no permission required - for select boxes).
     */
    public function getAwardTypeList()
    {
        try {
            $awardTypes = AwardType::active()
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'slug']);

            $this->logActivity('INDEX', 'Award Type', "Viewed award types list");

            return response()->json([
                'status' => 'success',
                'message' => 'Award types retrieved successfully',
                'data' => $awardTypes
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve award types',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle the status of the award type.
     */
    public function toggleStatus(string $id)
    {
        try {
            $award_type = AwardType::query()->find($id);

            if (!$award_type) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Award type not found',
                ], 404);
            }

            $award_type->update(['is_active' => !$award_type->is_active]);
            $status = $award_type->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Award Type', ucfirst($status) . " award type: {$award_type->name}");

            return response()->json([
                'status' => 'success',
                'message' => "Award type {$status} successfully",
                'data' => $award_type
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle award type status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
