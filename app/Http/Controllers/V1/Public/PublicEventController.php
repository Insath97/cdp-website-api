<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    /**
     * Display a listing of active and approved events.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Event::with(['galleries', 'tags'])
                ->where('status', 'approved')
                ->where('is_active', true);

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $query->orderBy('created_at', 'desc');
            $events = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Public events retrieved successfully',
                'data' => $events
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve public events',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display a single approved and active event.
     */
    public function show(string $idOrSlug)
    {
        try {
            $event = Event::with(['galleries', 'tags'])
                ->where(function ($query) use ($idOrSlug) {
                    $query->where('id', $idOrSlug)
                        ->orWhere('slug', $idOrSlug);
                })
                ->where('status', 'approved')
                ->where('is_active', true)
                ->first();

            if (! $event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Event retrieved successfully',
                'data' => $event
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve event',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
