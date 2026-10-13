<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class PublicTestimonialController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Testimonial::active()->select('id', 'name', 'city', 'designation', 'body', 'rating', 'thumbnail_image');

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            if ($request->has('rating')) {
                $query->where('rating', $request->rating);
            }

            $testimonials = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Testimonials retrieved successfully',
                'data' => $testimonials
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve testimonials',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
