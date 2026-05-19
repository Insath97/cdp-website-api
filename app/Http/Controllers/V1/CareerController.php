<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCareerRequest;
use App\Http\Requests\UpdateCareerRequest;
use App\Models\Benefit;
use App\Models\Career;
use App\Models\Requirement;
use App\Models\Responsibility;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CareerController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Career Index', only: ['index', 'show']),
            new Middleware('permission:Career Create', only: ['store']),
            new Middleware('permission:Career Update', only: ['update']),
            new Middleware('permission:Career Delete', only: ['destroy']),
            new Middleware('permission:Career Soft Delete', only: ['destroy']),
            new Middleware('permission:Career Force Delete', only: ['forceDelete']),
            new Middleware('permission:Career Restore', only: ['restore']),
            new Middleware('permission:Career Toggle Active', only: ['toggleStatus']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Career::with(['responsibilities', 'requirements', 'benefits']);

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

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
                'message' => 'Careers retrieved successfully',
                'data' => $careers,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve careers',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCareerRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Handle slug generation inside controller
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Handle poster image upload
            if ($request->hasFile('poster_image')) {
                $filepath = $this->handleFileUpload(
                    $request,
                    'poster_image',
                    null,
                    'careers',
                    Str::slug($data['title'])
                );

                if ($filepath) {
                    $data['poster_image'] = $filepath;
                }
            }

            $career = Career::create($data);

            // Sync relational lists
            $this->syncCareerRelations($career, $data);

            DB::commit();

            $this->logActivity(
                'CREATE',
                'Career',
                "Created career post: {$career->title}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Career post created successfully',
                'data' => $career->load([
                    'responsibilities',
                    'requirements',
                    'benefits',
                ]),
            ], 201);

        } catch (QueryException $qe) {

            DB::rollBack();

            if ((string) $qe->getCode() === '23000') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create career post',
                    'error' => 'Slug already exists. Please use a different title or slug.',
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create career post',
                'error' => config('app.debug')
                    ? $qe->getMessage()
                    : 'Internal server error',
            ], 500);

        } catch (\Throwable $th) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create career post',
                'error' => config('app.debug')
                    ? $th->getMessage()
                    : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $career = Career::with(['responsibilities', 'requirements', 'benefits'])
                ->where('id', $id)
                ->orWhere('slug', $id)
                ->first();

            if (! $career) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career post not found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Career post retrieved successfully',
                'data' => $career,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve career post',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCareerRequest $request, string $id)
    {
        try {
            $career = Career::query()->find($id);
            if (! $career) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career post not found',
                    'data' => [],
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();

            // Handle slug generation on update inside controller - exactly like EventController
            if (isset($data['title']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Handle poster image update
            if ($request->hasFile('poster_image')) {
                $filepath = $this->handleFileUpload($request, 'poster_image', $career->poster_image, 'careers', Str::slug($data['title'] ?? $career->title));
                if ($filepath) {
                    $data['poster_image'] = $filepath;
                }
            }

            $career->update($data);

            // Sync relational lists
            $this->syncCareerRelations($career, $data);

            DB::commit();

            $this->logActivity('UPDATE', 'Career', "Updated career post: {$career->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Career post updated successfully',
                'data' => $career->load(['responsibilities', 'requirements', 'benefits']),
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update career post',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Soft delete the career post.
     */
    public function destroy(string $id)
    {
        try {
            $career = Career::query()->find($id);
            if (! $career) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career post not found',
                    'data' => [],
                ], 404);
            }

            $title = $career->title;
            if (! Career::destroy($id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to soft delete career post',
                ], 500);
            }

            $this->logActivity('SOFT_DELETE', 'Career', "Soft deleted career post: {$title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Career post soft deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to soft delete career post',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Restore a soft deleted career post.
     */
    public function restore(string $id)
    {
        try {
            $career = Career::withTrashed()->find($id);
            if (! $career) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career post not found',
                    'data' => [],
                ], 404);
            }

            if (! $career->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career post is not deleted',
                ], 422);
            }

            $career->restore();

            $this->logActivity('RESTORE', 'Career', "Restored career post: {$career->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Career post restored successfully',
                'data' => $career->load(['responsibilities', 'requirements', 'benefits']),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore career post',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Permanently delete a career post.
     */
    public function forceDelete(string $id)
    {
        try {
            $career = Career::withTrashed()->find($id);
            if (! $career) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career post not found',
                    'data' => [],
                ], 404);
            }

            $title = $career->title;
            // Delete actual file
            $this->deleteFile($career->poster_image);
            $career->forceDelete();

            $this->logActivity('FORCE_DELETE', 'Career', "Permanently deleted career post: {$title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Career post permanently deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete career post',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Toggle the active status of the career.
     */
    public function toggleStatus(string $id)
    {
        try {
            $career = Career::query()->find($id);
            if (! $career) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career post not found',
                ], 404);
            }

            $career->update(['is_active' => ! $career->is_active]);
            $action = $career->is_active ? 'Activated' : 'Deactivated';

            $this->logActivity(strtoupper($action), 'Career', "{$action} career post: {$career->title}");

            return response()->json([
                'status' => 'success',
                'message' => "Career post {$action} successfully",
                'data' => $career->load(['responsibilities', 'requirements', 'benefits']),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle career status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Helper method to sync all career relations.
     */
    protected function syncCareerRelations(Career $career, array $data): void
    {
        // 1. Sync Responsibilities
        if (isset($data['key_responsibilities'])) {
            $responsibilityIds = [];
            foreach ($data['key_responsibilities'] as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }
                $model = Responsibility::firstOrCreate(['name' => $item]);
                $responsibilityIds[] = $model->id;
            }
            $career->responsibilities()->sync($responsibilityIds);
        }

        // 2. Sync Requirements
        if (isset($data['requirements'])) {
            $requirementIds = [];
            foreach ($data['requirements'] as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }
                $model = Requirement::firstOrCreate(['name' => $item]);
                $requirementIds[] = $model->id;
            }
            $career->requirements()->sync($requirementIds);
        }

        // 3. Sync Benefits
        if (isset($data['benefits'])) {
            $benefitIds = [];
            foreach ($data['benefits'] as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }
                $model = Benefit::firstOrCreate(['name' => $item]);
                $benefitIds[] = $model->id;
            }
            $career->benefits()->sync($benefitIds);
        }
    }
}
