<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateServicesRequest;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ActivityLogTrait;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServicesRequest;
use App\Models\Branch;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Str;


class ServiceController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

     /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Service Index', only: ['index', 'show']),
            new Middleware('permission:Service Create', only: ['store']),
            new Middleware('permission:Service Update', only: ['update']),
            new Middleware('permission:Service Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Service Delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

         try {
            $perPage = $request->get('per_page', 15);
            $query = Service::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $query->orderBy('created_at', 'desc');
            $services = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Services retrieved successfully',
                'data' => $services
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve services',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
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
    public function store(CreateServicesRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            $filepath = $this->handleFileUpload($request, 'imagepath', null, 'services/images',$data['slug']);

            if ($filepath) {
                $data['imagepath'] = $filepath;
            }

            $service = Service::create($data);

            DB::commit();

            $this->logActivity('CREATE', 'Service', "Created service: {$service->title} ({$service->slug})");

            return response()->json([
                'status' => 'success',
                'message' => 'Service created successfully',
                'data' => $service
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service',
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
            $service = Service::query()->find($id);

            if (!$service) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Service retrieved successfully',
                'data' => $service
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve service',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServicesRequest $request, string $id)
    {
         try {
            $service = Service::query()->find($id);

            if (!$service) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service not found',
                    'data' => []
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();

            // Update slug if name changed
            if (isset($data['title']) && $data['title'] !== $service->title) {
                $data['slug'] = Str::slug($data['title']);
            }

            if($request->hasFile('imagepath')) {
                $filepath = $this->handleFileUpload($request, 'imagepath', $service->imagepath, 'services/images', $data['slug'] ?? $service->slug);

                if ($filepath) {
                    $data['imagepath'] = $filepath;
                }
            }

            $service->update($data);

            DB::commit();

            $this->logActivity('UPDATE', 'Service', "Updated service: {$service->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Service updated successfully',
                'data' => $service
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update service',
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
            $service = Service::query()->find($id);

            if (!$service) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service not found',
                    'data' => []
                ], 404);
            }

            $serviceName = $service->name;
            $service->query()->delete();

            $this->logActivity('DELETE', 'Service', "Deleted service: {$serviceName}");

            return response()->json([
                'status' => 'success',
                'message' => 'Service deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete service',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle the status of the service.
     */
    public function toggleStatus(string $id)
    {
        try {
            $service = Service::query()->find($id);

            if (!$service) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service not found',
                ], 404);
            }

            $service->update(['is_active' => !$service->is_active]);
            $status = $service->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Service', ucfirst($status) . " service: {$service->title}");

            return response()->json([
                'status' => 'success',
                'message' => "Service {$status} successfully",
                'data' => $service
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle service status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
