<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
<<<<<<< HEAD
=======
use Illuminate\Http\Request;
>>>>>>> 0fdb880 (Added contact module)
use App\Models\Event;
use App\Models\EventGallery;
use App\Models\Tag;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Event Index', only: ['index', 'show']),
            new Middleware('permission:Event Create', only: ['create', 'store']),
            new Middleware('permission:Event Update', only: ['edit', 'update']),
            new Middleware('permission:Event Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Event Restore', only: ['restore']),
            new Middleware('permission:Event Force Delete', only: ['forceDelete']),
            new Middleware('permission:Event Delete', only: ['destroy', 'softDelete']),
            new Middleware('permission:Event Approve', only: ['approve', 'reject']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Event::with(['galleries', 'tags']);

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
                'data' => $events,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve events',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateEventRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
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
                    return response()->json([
                        'status' => 'error',
                        'message' => 'rejected_reason is required when rejecting',
                    ], 422);
                }
            }

            $event = Event::create($data);

            // Handle multiple image uploads for galleries
            $galleryPaths = $this->handleMultipleFileUpload(
                $request,
                'galleries',
                [],
                'events/'.$event->id,
                Str::slug($data['slug'].'-'.$event->id)
            );

            foreach ($galleryPaths as $path) {
                EventGallery::create([
                    'event_id' => $event->id,
                    'image_path' => $path,
                ]);
            }

            // Handle tags
            if (! empty($data['tags'])) {
                $tags = explode(',', $data['tags']);
                $tagIds = [];

                foreach ($tags as $tag) {
                    $tag = trim($tag);

                    if ($tag === '') {
                        continue;
                    }

                    $tagModel = Tag::firstOrCreate(
                        ['name' => $tag],
                        ['slug' => Str::slug($tag)]
                    );

                    $tagIds[] = $tagModel->id;
                }

                $event->tags()->sync($tagIds);
            }

            DB::commit();

            $this->logActivity('CREATE', 'Event', "Created event: {$event->title}");

            $event->load(['galleries', 'tags']);

            return response()->json([
                'status' => 'success',
                'message' => 'Event created successfully',
                'data' => $event,
            ], 201);
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
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error',
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $event = Event::with(['galleries', 'tags', 'createdBy', 'decisionBy'])
                ->where('id', $id)
                ->orWhere('slug', $id)
                ->first();
            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Event retrieved successfully',
                'data' => $event,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, string $id)
    {
        try {
            $event = Event::query()->find($id);
            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                    'data' => [],
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();

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
                            return response()->json([
                                'status' => 'error',
                                'message' => 'rejected_reason is required when rejecting',
                            ], 422);
                        }
                    } elseif ($new === Event::STATUS_PENDING) {
                        $data['decision_by'] = null;
                        $data['decision_at'] = null;
                        $data['rejected_reason'] = null;
                    }
                }
            }

            $event->update($data);

            $galleryImagePaths = [];
            if ($request->hasFile('galleries')) {
                $oldPaths = $event->galleries()->pluck('image_path')->toArray();
                $this->deleteMultipleFiles($oldPaths);
                $event->galleries()->delete();

                $galleryPaths = $this->handleMultipleFileUpload($request, 'galleries', $oldPaths, 'events', Str::slug($data['title'] ?? $event->title));
                foreach ($galleryPaths as $path) {
                    EventGallery::create([
                        'event_id' => $event->id,
                        'image_path' => $path,
                    ]);
                }
            }

            // Handle tags (replace all)
            if (isset($data['tags'])) {
                if (! empty($data['tags'])) {
                    $tags = explode(',', $data['tags']);
                    $tagIds = [];

                    foreach ($tags as $tag) {
                        $tag = trim($tag);

                        if ($tag === '') {
                            continue;
                        }

                        $tagModel = Tag::firstOrCreate(
                            ['name' => $tag],
                            ['slug' => Str::slug($tag)]
                        );

                        $tagIds[] = $tagModel->id;
                    }

                    $event->tags()->sync($tagIds);
                } else {
                    $event->tags()->detach();
                }
            }

            DB::commit();

            $this->logActivity('UPDATE', 'Event', "Updated event: {$event->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Event updated successfully',
                'data' => $event->load(['galleries', 'tags']),
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $event = Event::query()->find($id);
            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                    'data' => [],
                ], 404);
            }

            $name = $event->title;
<<<<<<< HEAD
            if (! $event->query()->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to soft delete event',
                ], 500);
=======
            if (!$event->query()->delete()) {
                return response()->json(['status' => 'error', 'message' => 'Failed to soft delete event'], 500);
>>>>>>> 0fdb880 (Added contact module)
            }

            $this->logActivity('SOFT_DELETE', 'Event', "Soft deleted event: {$name}");

            return response()->json([
                'status' => 'success',
                'message' => 'Event soft deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to soft delete event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Restore a soft deleted resource.
     */
    public function restore(string $id)
    {
        try {
            $event = Event::withTrashed()->find($id);
            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                    'data' => [],
                ], 404);
            }

            if (! $event->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event is not deleted',
                ], 422);
            }

            $event->restore();

            $this->logActivity('RESTORE', 'Event', "Restored event: {$event->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Event restored successfully',
                'data' => $event,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Permanently delete a resource.
     */
    public function forceDelete(string $id)
    {
        try {
            $event = Event::withTrashed()->find($id);
            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                    'data' => [],
                ], 404);
            }

            $name = $event->title;
            $this->deleteFile($event->thumbnail_image);
            $event->forceDelete();

            $this->logActivity('FORCE_DELETE', 'Event', "Force deleted event: {$name}");

            return response()->json([
                'status' => 'success',
                'message' => 'Event permanently deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Toggle the active status of the event.
     */
    public function toggleStatus(string $id)
    {
        try {
            $event = Event::query()->find($id);
            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                ], 404);
            }

            $event->update(['is_active' => ! $event->is_active]);
            $action = $event->is_active ? 'Activated' : 'Deactivated';

            $this->logActivity(strtoupper($action), 'Event', "{$action} event: {$event->title}");

            return response()->json([
                'status' => 'success',
                'message' => "Event {$action} successfully",
                'data' => $event,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle event status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Approve the event (requires Event Approve permission).
     */
    public function approve(string $id)
    {
        try {
            $event = Event::query()->find($id);
            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                    'data' => [],
                ], 404);
            }

            if ($event->status === 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event is already approved',
                ], 422);
            }

            $event->update([
                'status' => 'approved',
                'decision_by' => Auth::id(),
                'decision_at' => now(),
                'rejected_reason' => null,
            ]);

            $this->logActivity('APPROVE', 'Event', "Approved event: {$event->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Event approved successfully',
                'data' => $event,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject the event (requires Event Approve permission).
     */
    public function reject(Request $request, string $id)
    {
        try {
            $event = Event::query()->find($id);
            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                    'data' => [],
                ], 404);
            }

            if ($event->status === 'rejected') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event is already rejected',
                ], 422);
            }

            $request->validate([
                'rejected_reason' => 'required|string|max:1000',
            ]);

            $event->update([
                'status' => 'rejected',
                'decision_by' => Auth::id(),
                'decision_at' => now(),
                'rejected_reason' => $request->rejected_reason,
            ]);

            $this->logActivity('REJECT', 'Event', "Rejected event: {$event->title}");

            return response()->json([
                'status' => 'success',
                'message' => 'Event rejected successfully',
                'data' => $event,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

  
}
