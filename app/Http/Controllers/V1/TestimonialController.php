<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Testimonial;
use App\Http\Requests\CreateTestimonialRequest;
use App\Http\Requests\UpdateTestimonialRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TestimonialController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Testimonial Index', only: ['index', 'show']),
            new Middleware('permission:Testimonial Create', only: ['store']),
            new Middleware('permission:Testimonial Update', only: ['update']),
            new Middleware('permission:Testimonial Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Testimonial Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Testimonial::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('rating')) {
                $query->where('rating', $request->rating);
            }

            $query->orderBy('created_at', 'desc');
            $testimonials = $query->paginate($perPage);

            $this->logActivity('INDEX', 'Testimonial', "Viewed testimonials list");

            return response()->json([
                'status' => true,
                'message' => 'Testimonials fetched successfully',
                'data' => $testimonials
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
    public function store(CreateTestimonialRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail_image')) {
                $filepath = $this->handleFileUpload($request, 'thumbnail_image', null, 'testimonials', Str::slug($data['name']));
                if ($filepath) {
                    $data['thumbnail_image'] = $filepath;
                }
            }

            $testimonial = Testimonial::create($data);

            DB::commit();

            $this->logActivity('CREATE', 'Testimonial', "Created testimonial from: {$testimonial->name}");

            return response()->json([
                'status' => true,
                'message' => 'Testimonial created successfully',
                'data' => $testimonial
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create testimonial',
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
            $testimonial = Testimonial::query()->find($id);

            if (!$testimonial) {
                return response()->json([
                    'status' => false,
                    'message' => 'Testimonial not found',
                    'data' => null
                ], 404);
            }

            $this->logActivity('SHOW', 'Testimonial', "Viewed testimonial details from: {$testimonial->name}", ['testimonial_id' => $testimonial->id]);

            return response()->json([
                'status' => true,
                'message' => 'Testimonial fetched successfully',
                'data' => $testimonial
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
    public function update(UpdateTestimonialRequest $request, string $id)
    {
        try {
            $testimonial = Testimonial::query()->find($id);

            if (!$testimonial) {
                return response()->json([
                    'status' => false,
                    'message' => 'Testimonial not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();

            // Handle thumbnail swap (old file deleted by trait)
            if ($request->hasFile('thumbnail_image')) {
                $filepath = $this->handleFileUpload(
                    $request,
                    'thumbnail_image',
                    $testimonial->thumbnail_image,
                    'testimonials',
                    Str::slug($data['name'] ?? $testimonial->name)
                );
                if ($filepath) {
                    $data['thumbnail_image'] = $filepath;
                }
            }

            $testimonial->update($data);

            DB::commit();

            $this->logActivity('UPDATE', 'Testimonial', "Updated testimonial from: {$testimonial->name}");

            return response()->json([
                'status' => true,
                'message' => 'Testimonial updated successfully',
                'data' => $testimonial
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
     * Remove the specified resource from storage (purges files).
     */
    public function destroy(string $id)
    {
        try {
            $testimonial = Testimonial::find($id);

            if (!$testimonial) {
                return response()->json([
                    'status' => false,
                    'message' => 'Testimonial not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $testimonialName = $testimonial->name;

            // Delete actual file
            $this->deleteFile($testimonial->thumbnail_image);

            $testimonial->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Testimonial', "Deleted testimonial from: {$testimonialName}");

            return response()->json([
                'status' => true,
                'message' => 'Testimonial deleted successfully',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete testimonial',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle the status of the testimonial.
     */
    public function toggleStatus(string $id)
    {
        try {
            $testimonial = Testimonial::query()->find($id);

            if (!$testimonial) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Testimonial not found',
                ], 404);
            }

            $testimonial->update(['is_active' => !$testimonial->is_active]);
            $status = $testimonial->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Testimonial', ucfirst($status) . " testimonial from: {$testimonial->name}");

            return response()->json([
                'status' => 'success',
                'message' => "Testimonial {$status} successfully",
                'data' => $testimonial
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle testimonial status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
