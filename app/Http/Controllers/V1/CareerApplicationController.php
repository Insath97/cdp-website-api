<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCareerApplicationStatusRequest;
use App\Models\CareerApplication;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CareerApplicationController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Career Application Index', only: ['index']),
            new Middleware('permission:Career Application Show', only: ['show']),
            new Middleware('permission:Career Application Update Status', only: ['updateStatus']),
        ];
    }

    /**
     * Display a listing of career applications.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = CareerApplication::with('career');

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('fullname', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('phone_number', 'LIKE', "%{$search}%")
                      ->orWhere('application_code', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->has('career_id') && $request->career_id != '') {
                $query->where('career_id', $request->career_id);
            }

            $query->orderBy('created_at', 'desc');
            $applications = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Career applications retrieved successfully',
                'data' => $applications,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve career applications',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified career application.
     */
    public function show(string $id)
    {
        try {
            $application = CareerApplication::with('career')->find($id);

            if (!$application) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career application not found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Career application retrieved successfully',
                'data' => $application,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve career application',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the status of the career application.
     */
    public function updateStatus(UpdateCareerApplicationStatusRequest $request, string $id)
    {
        try {
            $application = CareerApplication::query()->find($id);

            if (!$application) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career application not found',
                ], 404);
            }

            $oldStatus = $application->status;
            $newStatus = $request->validated()['status'];

            $application->update(['status' => $newStatus]);

            $this->logActivity(
                'UPDATE_STATUS',
                'Career Application',
                "Updated status of application {$application->application_code} from '{$oldStatus}' to '{$newStatus}'"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Career application status updated successfully',
                'data' => $application->load('career'),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update career application status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
