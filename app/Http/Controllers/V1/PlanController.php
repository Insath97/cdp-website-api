<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PlanController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Plan Index', only: ['index', 'show']),
            new Middleware('permission:Plan Create', only: ['store']),
            new Middleware('permission:Plan Update', only: ['update']),
            new Middleware('permission:Plan Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Plan Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Plan::with('features');

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $query->orderBy('created_at', 'desc');
            $plans = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Plans retrieved successfully',
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlanRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            
            // Handle file upload
            $filepath = $this->handleFileUpload($request, 'image', null, 'plans', Str::slug($data['maintitle']));
            if ($filepath) {
                $data['image'] = $filepath;
            }

            $plan = Plan::create($data);

            if (isset($data['features'])) {
                foreach ($data['features'] as $feature) {
                    $plan->features()->create($feature);
                }
            }

            DB::commit();

            $this->logActivity('CREATE', 'Plan', "Created plan: {$plan->maintitle}");

            return response()->json([
                'status' => 'success',
                'message' => 'Plan created successfully',
                'data' => $plan->load('features')
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create plan',
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
            $plan = Plan::with('features')->find($id);

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Plan not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Plan retrieved successfully',
                'data' => $plan
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve plan',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlanRequest $request, string $id)
    {
        try {
            $plan = Plan::find($id);

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Plan not found',
                    'data' => []
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();

            // Handle file upload if a new image is provided
            if ($request->hasFile('image')) {
                $filepath = $this->handleFileUpload($request, 'image', $plan->image, 'plans', Str::slug($data['maintitle']));
                if ($filepath) {
                    $data['image'] = $filepath;
                }
            }

            $plan->update($data);

            if (isset($data['features'])) {
                $plan->features()->delete();
                foreach ($data['features'] as $feature) {
                    $plan->features()->create($feature);
                }
            }

            DB::commit();

            $this->logActivity('UPDATE', 'Plan', "Updated plan: {$plan->maintitle}");

            return response()->json([
                'status' => 'success',
                'message' => 'Plan updated successfully',
                'data' => $plan->load('features')
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update plan',
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
            $plan = Plan::find($id);

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Plan not found',
                    'data' => []
                ], 404);
            }

            // Delete associated image
            $this->deleteFile($plan->image);

            $planName = $plan->maintitle;
            $plan->delete();

            $this->logActivity('DELETE', 'Plan', "Deleted plan: {$planName}");

            return response()->json([
                'status' => 'success',
                'message' => 'Plan deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete plan',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle the status of the plan.
     */
    public function toggleStatus(string $id)
    {
        try {
            $plan = Plan::find($id);

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Plan not found',
                ], 404);
            }

            $plan->update(['is_active' => !$plan->is_active]);
            $status = $plan->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Plan', ucfirst($status) . " plan: {$plan->maintitle}");

            return response()->json([
                'status' => 'success',
                'message' => "Plan {$status} successfully",
                'data' => $plan
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle plan status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
