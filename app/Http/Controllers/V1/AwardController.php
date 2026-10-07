<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAwardRequest;
use App\Http\Requests\UpdateAwardRequest;
use App\Models\Award;
use App\Models\AwardGallery;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AwardController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Award Index', only: ['index', 'show']),
            new Middleware('permission:Award Create', only: ['store']),
            new Middleware('permission:Award Update', only: ['update']),
            new Middleware('permission:Award Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Award Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Award::with(['awardType', 'galleries']);

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('verified')) {
                $query->where('verified', $request->boolean('verified'));
            }

            if ($request->has('award_type_id')) {
                $query->where('award_type_id', $request->award_type_id);
            }

            $query->orderBy('created_at', 'desc');
            $awards = $query->paginate($perPage);

            $this->logActivity('INDEX', 'Award', "Viewed awards list");

            return response()->json([
                'status' => true,
                'message' => 'Awards fetched successfully',
                'data' => $awards
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
    public function store(CreateAwardRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if (empty($data['slug']) && !empty($data['title'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail_image')) {
                $filepath = $this->handleFileUpload($request, 'thumbnail_image', null, 'awards', Str::slug($data['title']));
                if ($filepath) {
                    $data['thumbnail_image'] = $filepath;
                }
            }

            $award = Award::create($data);

            // Handle gallery images
            $galleryPaths = $this->handleMultipleFileUpload(
                $request,
                'galleries',
                [],
                'awards/' . $award->id,
                Str::slug($data['title'] . '-' . $award->id)
            );

            foreach ($galleryPaths as $path) {
                AwardGallery::create([
                    'award_id' => $award->id,
                    'image_path' => $path,
                ]);
            }

            DB::commit();

            $this->logActivity('CREATE', 'Award', "Created award: {$award->title}");

            $award->load(['awardType', 'galleries']);

            return response()->json([
                'status' => true,
                'message' => 'Award created successfully',
                'data' => $award
            ], 201);

        } catch (QueryException $qe) {
            DB::rollBack();
            if ((string) $qe->getCode() === '23000') {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create award',
                    'error' => 'Slug already exists. Please use a different title or slug.',
                ], 422);
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to create award',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create award',
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
            $award = Award::with(['awardType', 'galleries'])->find($id);

            if (!$award) {
                return response()->json([
                    'status' => false,
                    'message' => 'Award not found',
                    'data' => null
                ], 404);
            }

            $this->logActivity('SHOW', 'Award', "Viewed award details: {$award->title}", ['award_id' => $award->id]);

            return response()->json([
                'status' => true,
                'message' => 'Award fetched successfully',
                'data' => $award
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
    public function update(UpdateAwardRequest $request, string $id)
    {
        try {
            $award = Award::with('galleries')->find($id);

            if (!$award) {
                return response()->json([
                    'status' => false,
                    'message' => 'Award not found',
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
                    $award->thumbnail_image,
                    'awards',
                    Str::slug($data['title'] ?? $award->title)
                );
                if ($filepath) {
                    $data['thumbnail_image'] = $filepath;
                }
            }

            $award->update($data);

            // Replace gallery images only when new ones are sent
            if ($request->hasFile('galleries')) {
                $oldPaths = $award->galleries()->pluck('image_path')->toArray();
                $this->deleteMultipleFiles($oldPaths);
                $award->galleries()->delete();

                $galleryPaths = $this->handleMultipleFileUpload(
                    $request,
                    'galleries',
                    [],
                    'awards/' . $award->id,
                    Str::slug(($data['title'] ?? $award->title) . '-' . $award->id)
                );

                foreach ($galleryPaths as $path) {
                    AwardGallery::create([
                        'award_id' => $award->id,
                        'image_path' => $path,
                    ]);
                }
            }

            DB::commit();

            $this->logActivity('UPDATE', 'Award', "Updated award: {$award->title}");

            $award->load(['awardType', 'galleries']);

            return response()->json([
                'status' => true,
                'message' => 'Award updated successfully',
                'data' => $award
            ], 200);

        } catch (QueryException $qe) {
            DB::rollBack();
            if ((string) $qe->getCode() === '23000') {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to update award',
                    'error' => 'Slug already exists. Please use a different title or slug.',
                ], 422);
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to update award',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update award',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (purges files).
     */
    public function destroy(string $id)
    {
        try {
            $award = Award::with('galleries')->find($id);

            if (!$award) {
                return response()->json([
                    'status' => false,
                    'message' => 'Award not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $awardTitle = $award->title;

            // Delete actual files
            $this->deleteFile($award->thumbnail_image);
            $galleryPaths = $award->galleries()->pluck('image_path')->toArray();
            $this->deleteMultipleFiles($galleryPaths);

            $award->galleries()->delete();
            $award->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Award', "Deleted award: {$awardTitle}");

            return response()->json([
                'status' => true,
                'message' => 'Award deleted successfully',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete award',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle the status of the award.
     */
    public function toggleStatus(string $id)
    {
        try {
            $award = Award::find($id);

            if (!$award) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Award not found',
                    'data' => null
                ], 404);
            }

            $award->update(['is_active' => !$award->is_active]);
            $status = $award->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Award', ucfirst($status) . " award: {$award->title}");

            return response()->json([
                'status' => 'success',
                'message' => "Award {$status} successfully",
                'data' => $award
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle award status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
