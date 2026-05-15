<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventRequest;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;


class EventController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Resolve an event by id or slug.
     */
    private function findEventByIdOrSlug(string $idOrSlug, bool $withTrashed = false): ?Event
    {
        $query = $withTrashed ? Event::withTrashed() : Event::query();

        $event = $query->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();

        if (! $event) {
            Log::info('Event lookup failed', ['identifier' => $idOrSlug, 'withTrashed' => $withTrashed]);
        }

        return $event;
    }

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Event Index', only: ['index', 'show']),
            new Middleware('permission:Event Create', only: ['create', 'store']),
            new Middleware('permission:Event Update', only: ['edit', 'update']),
            new Middleware('permission:Event Toggle Active', only: ['activate', 'deactivate']),
            new Middleware('permission:Event Restore', only: ['restore']),
            new Middleware('permission:Event Force Delete', only: ['forceDelete']),
            new Middleware('permission:Event Delete', only: ['destroy', 'softDelete']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Event::with('galleries');

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $query->orderBy('created_at', 'desc');
            $events = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Events retrieved successfully',
                'data' => $events
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve events',
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
    public function store(CreateEventRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Validate galleries separately to preserve file objects
            if ($request->hasFile('galleries')) {
                $galleryValidator = validator()->make($request->allFiles(), [
                    'galleries.*' => 'image|mimes:jpeg,png,jpg|max:10240',
                ]);
                if ($galleryValidator->fails()) {
                    return response()->json(['status' => 'error', 'message' => 'Gallery validation failed', 'errors' => $galleryValidator->errors()], 422);
                }
            }

            if (empty($data['slug'])) {
                $base = Str::slug($data['title']);
                $slug = $base;
                $i = 1;
                while (Event::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $data['slug'] = $slug;
            }

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail_image')) {
                $filepath = $this->handleFileUpload($request, 'thumbnail_image', null, 'events', Str::slug($data['title']));
                if ($filepath) {
                    $data['thumbnail_image'] = $filepath;
                }
            }

            $data['created_by'] = Auth::id();

            $data['status'] = $data['status'] ?? Event::STATUS_PENDING;

            if (in_array($data['status'], [Event::STATUS_APPROVED, Event::STATUS_REJECTED])) {
                $data['decision_by'] = Auth::id();
                $data['decision_at'] = now();
                if ($data['status'] === Event::STATUS_REJECTED && empty($data['rejected_reason'])) {
                    return response()->json(['status' => 'error', 'message' => 'rejected_reason is required when rejecting'], 422);
                }
            }

            $event = Event::create($data);

            // Handle gallery file uploads
            Log::info('Store galleries check', [
                'has_file' => $request->hasFile('galleries'),
                'all_files' => array_keys($request->allFiles()),
                'all_input' => array_keys($request->all()),
                'request_method' => $request->method(),
                'content_type' => $request->header('content-type'),
            ]);

            // Try different keys to find gallery files
            $galleryFiles = [];
            if ($request->hasFile('galleries')) {
                $galleryFiles = $request->file('galleries');
            } elseif ($request->hasFile('gallery')) {
                $galleryFiles = $request->file('gallery');
            }

            // Ensure it's an array
            if (!empty($galleryFiles)) {
                if (!is_array($galleryFiles)) {
                    $galleryFiles = [$galleryFiles];
                }

                Log::info('Processing gallery files', [
                    'file_count' => count($galleryFiles),
                    'first_file' => isset($galleryFiles[0]) ? get_class($galleryFiles[0]) : 'none',
                ]);

                // Manual file upload instead of using handleMultipleFileUpload
                foreach ($galleryFiles as $index => $file) {
                    if (!$file || !$file->isValid()) {
                        Log::warning("Invalid file at index $index", ['error' => $file ? $file->getError() : 'null file']);
                        continue;
                    }

                    try {
                        $extension = $file->getClientOriginalExtension();
                        $fileName = Str::slug($data['title']) . '_' . ($index + 1) . '.' . $extension;
                        $directory = 'uploads/events';
                        $filePath = $directory . '/' . $fileName;

                        // Create directory if not exists
                        if (!File::exists(public_path($directory))) {
                            File::makeDirectory(public_path($directory), 0755, true);
                        }

                        $file->move(public_path($directory), $fileName);

                        Log::info('Uploaded gallery file', ['path' => $filePath, 'file_exists' => file_exists(public_path($filePath))]);

                        $event->galleries()->create(['image_path' => $filePath]);
                    } catch (\Exception $e) {
                        Log::error('Gallery upload error', ['error' => $e->getMessage()]);
                    }
                }
            } else {
                Log::info('No gallery files found in request');
            }

            Log::info('Event galleries final', [
                'event_id' => $event->id,
                'gallery_count' => $event->galleries()->count(),
            ]);

            DB::commit();

            $this->logActivity('CREATE', 'Event', "Created event: {$event->title}");

            // Refresh event to ensure galleries are loaded
            $event->refresh();

            $response = response()->json(['status' => 'success', 'message' => 'Event created successfully', 'data' => $event->load('galleries')], 201);
            $response->headers->set('Location', url('/api/v1/events/' . $event->id));
            return $response;
        } catch (QueryException $qe) {
            DB::rollBack();

            if ((string) $qe->getCode() === '23000') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create event',
                    'error' => 'Slug already exists. Please use a different title or slug.',
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create event',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create event',
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
            $event = Event::with(['galleries', 'createdBy', 'decisionBy'])
                ->where('id', $id)
                ->orWhere('slug', $id)
                ->first();
            if (! $event) {
                return response()->json(['status' => 'error', 'message' => 'Event not found', 'data' => []], 404);
            }

            return response()->json(['status' => 'success', 'message' => 'Event retrieved successfully', 'data' => $event], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => 'Failed to retrieve event', 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'], 500);
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
    public function update(Request $request, string $id)
    {
        try {
            $event = $this->findEventByIdOrSlug($id);
            if (! $event) {
                return response()->json(['status' => 'error', 'message' => 'Event not found', 'data' => []], 404);
            }

            DB::beginTransaction();

            $rules = [
                'title' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255|unique:events,slug,' . $id,
                'created_date' => 'sometimes|required|date',
                'thumbnail_image' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
                'url' => 'nullable|url',
                'description' => 'sometimes|required|string',
                'is_active' => 'boolean',
                'status' => 'in:pending,approved,rejected',
                'rejected_reason' => 'nullable|string',
            ];

            $data = $request->validate($rules);

            if ($request->hasFile('thumbnail_image')) {
                $filepath = $this->handleFileUpload($request, 'thumbnail_image', $event->thumbnail_image, 'events', Str::slug($data['title'] ?? $event->title));
                if ($filepath) {
                    $data['thumbnail_image'] = $filepath;
                }
            }

            if (array_key_exists('status', $data)) {
                $new = $data['status'];
                $old = $event->status;
                if ($new !== $old) {
                    if (in_array($new, [Event::STATUS_APPROVED, Event::STATUS_REJECTED])) {
                        $data['decision_by'] = Auth::id();
                        $data['decision_at'] = now();
                        if ($new === Event::STATUS_REJECTED && empty($data['rejected_reason'])) {
                            return response()->json(['status' => 'error', 'message' => 'rejected_reason is required when rejecting'], 422);
                        }
                    } elseif ($new === Event::STATUS_PENDING) {
                        $data['decision_by'] = null;
                        $data['decision_at'] = null;
                        $data['rejected_reason'] = null;
                    }
                }
            }

            $event->update($data);

            if ($request->hasFile('galleries')) {
                $event->galleries()->delete();
                $galleryFiles = $request->file('galleries');
                if (!is_array($galleryFiles)) {
                    $galleryFiles = [$galleryFiles];
                }

                foreach ($galleryFiles as $index => $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $extension = $file->getClientOriginalExtension();
                    $fileName = Str::slug($data['title'] ?? $event->title) . '_' . ($index + 1) . '.' . $extension;
                    $directory = 'uploads/events';
                    $filePath = $directory . '/' . $fileName;

                    if (!File::exists(public_path($directory))) {
                        File::makeDirectory(public_path($directory), 0755, true);
                    }

                    $file->move(public_path($directory), $fileName);
                    $event->galleries()->create(['image_path' => $filePath]);
                }
            } elseif (array_key_exists('galleries', $data)) {
                $event->galleries()->delete();
                foreach ($data['galleries'] as $g) {
                    $event->galleries()->create([
                        'image_path' => is_string($g) ? $g : ($g['image_path'] ?? null)
                    ]);
                }
            }

            DB::commit();

            $this->logActivity('UPDATE', 'Event', "Updated event: {$event->title}");

            return response()->json(['status' => 'success', 'message' => 'Event updated successfully', 'data' => $event->load('galleries')], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update event', 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->softDelete($id);
    }

    /**
     * Soft delete the specified resource.
     */
    public function softDelete(string $id)
    {
        try {
            $event = $this->findEventByIdOrSlug($id);
            if (! $event instanceof Event) {
                return response()->json(['status' => 'error', 'message' => 'Event not found', 'data' => []], 404);
            }

            $name = $event->title;
            if (! $event->query()->delete()) {
                return response()->json(['status' => 'error', 'message' => 'Failed to soft delete event'], 500);
            }

            $this->logActivity('SOFT_DELETE', 'Event', "Soft deleted event: {$name}");

            return response()->json(['status' => 'success', 'message' => 'Event soft deleted successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => 'Failed to soft delete event', 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Restore a soft deleted resource.
     */
    public function restore(string $id)
    {
        try {
            $event = $this->findEventByIdOrSlug($id, true);
            if (! $event) {
                return response()->json(['status' => 'error', 'message' => 'Event not found', 'data' => []], 404);
            }

            if (! $event->trashed()) {
                return response()->json(['status' => 'error', 'message' => 'Event is not deleted'], 422);
            }

            $event->restore();

            $this->logActivity('RESTORE', 'Event', "Restored event: {$event->title}");

            return response()->json(['status' => 'success', 'message' => 'Event restored successfully', 'data' => $event], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => 'Failed to restore event', 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Permanently delete a resource.
     */
    public function forceDelete(string $id)
    {
        try {
            $event = $this->findEventByIdOrSlug($id, true);
            if (! $event) {
                return response()->json(['status' => 'error', 'message' => 'Event not found', 'data' => []], 404);
            }

            $name = $event->title;
            $this->deleteFile($event->thumbnail_image);
            $event->forceDelete();

            $this->logActivity('FORCE_DELETE', 'Event', "Force deleted event: {$name}");

            return response()->json(['status' => 'success', 'message' => 'Event permanently deleted successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => 'Failed to permanently delete event', 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Activate the event.
     */
    public function activate(string $id)
    {
        try {
            $event = $this->findEventByIdOrSlug($id);
            if (! $event) {
                return response()->json(['status' => 'error', 'message' => 'Event not found'], 404);
            }

            if ($event->is_active) {
                return response()->json(['status' => 'error', 'message' => 'Event is already active'], 422);
            }

            $event->update(['is_active' => true]);

            $this->logActivity('ACTIVATE', 'Event', "Activated event: {$event->title}");

            return response()->json(['status' => 'success', 'message' => 'Event activated successfully', 'data' => $event], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => 'Failed to activate event', 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Deactivate the event.
     */
    public function deactivate(string $id)
    {
        try {
            $event = $this->findEventByIdOrSlug($id);
            if (! $event) {
                return response()->json(['status' => 'error', 'message' => 'Event not found'], 404);
            }

            if (! $event->is_active) {
                return response()->json(['status' => 'error', 'message' => 'Event is already inactive'], 422);
            }

            $event->update(['is_active' => false]);

            $this->logActivity('DEACTIVATE', 'Event', "Deactivated event: {$event->title}");

            return response()->json(['status' => 'success', 'message' => 'Event deactivated successfully', 'data' => $event], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => 'Failed to deactivate event', 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'], 500);
        }
    }
}
