<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProjectController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Project Index', only: ['index', 'show']),
            new Middleware('permission:Project Create', only: ['store']),
            new Middleware('permission:Project Update', only: ['update']),
            new Middleware('permission:Project Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Project Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Project::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->has('location') && $request->location != '') {
                $query->where('location', 'LIKE', "%{$request->location}%");
            }

            $query->orderBy('created_at', 'desc');
            $projects = $query->paginate($perPage);

            $this->logActivity('INDEX', 'Project', "Viewed projects list");

            return response()->json([
                'status' => true,
                'message' => 'Projects fetched successfully',
                'data' => $projects
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
     * Store a newly created resource in storage.
     */
    public function store(CreateProjectRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if (empty($data['slug']) && !empty($data['title'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail_image')) {
                $filepath = $this->handleFileUpload($request, 'thumbnail_image', null, 'projects', Str::slug($data['title']));
                if ($filepath) {
                    $data['thumbnail_image'] = $filepath;
                }
            }

            $project = Project::create($data);

            DB::commit();

            $this->logActivity('CREATE', 'Project', "Created project: {$project->title}");

            return response()->json([
                'status' => true,
                'message' => 'Project created successfully',
                'data' => $project
            ], 201);

        } catch (QueryException $qe) {
            DB::rollBack();
            if ((string) $qe->getCode() === '23000') {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create project',
                    'error' => 'Slug already exists. Please use a different title or slug.',
                ], 422);
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to create project',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create project',
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
            $project = Project::find($id);

            if (!$project) {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => null
                ], 404);
            }

            $this->logActivity('SHOW', 'Project', "Viewed project details: {$project->title}", ['project_id' => $project->id]);

            return response()->json([
                'status' => true,
                'message' => 'Project fetched successfully',
                'data' => $project
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
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, string $id)
    {
        try {
            $project = Project::find($id);

            if (!$project) {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();

            if (empty($data['slug']) && !empty($data['title'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Handle thumbnail swap (old file deleted by trait)
            if ($request->hasFile('thumbnail_image')) {
                $filepath = $this->handleFileUpload(
                    $request,
                    'thumbnail_image',
                    $project->thumbnail_image,
                    'projects',
                    Str::slug($data['title'] ?? $project->title)
                );
                if ($filepath) {
                    $data['thumbnail_image'] = $filepath;
                }
            }

            $project->update($data);

            DB::commit();

            $this->logActivity('UPDATE', 'Project', "Updated project: {$project->title}");

            return response()->json([
                'status' => true,
                'message' => 'Project updated successfully',
                'data' => $project
            ], 200);

        } catch (QueryException $qe) {
            DB::rollBack();
            if ((string) $qe->getCode() === '23000') {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to update project',
                    'error' => 'Slug already exists. Please use a different title or slug.',
                ], 422);
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to update project',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update project',
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
            $project = Project::find($id);

            if (!$project) {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $projectTitle = $project->title;

            // Delete actual thumbnail file
            $this->deleteFile($project->thumbnail_image);

            $project->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Project', "Deleted project: {$projectTitle}");

            return response()->json([
                'status' => true,
                'message' => 'Project deleted successfully',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete project',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle the status of the project.
     */
    public function toggleStatus(string $id)
    {
        try {
            $project = Project::find($id);

            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found',
                    'data' => null
                ], 404);
            }

            $project->update(['is_active' => !$project->is_active]);
            $status = $project->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Project', ucfirst($status) . " project: {$project->title}");

            return response()->json([
                'status' => 'success',
                'message' => "Project {$status} successfully",
                'data' => $project
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle project status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
