<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\FaqType;
use Illuminate\Http\Request;

class PublicFaqController extends Controller
{
    public function getFaqTypes(Request $request)
    {
        try {
            $query = FaqType::active()->select('id', 'name', 'slug', 'description');

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $faqTypes = $query->orderBy('name', 'asc')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ types retrieved successfully',
                'data' => $faqTypes
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve FAQ types',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getFaqTypeBySlug(string $slug)
    {
        try {
            $faqType = FaqType::active()
                ->where('slug', $slug)
                ->select('id', 'name', 'slug', 'description')
                ->first();

            if (!$faqType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'FAQ type not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ type retrieved successfully',
                'data' => $faqType
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve FAQ type',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getFaqs(Request $request)
    {
        try {
            $query = Faq::active()->with('faqType:id,name,slug');

            if ($request->has('faq_type_id')) {
                $query->where('faq_type_id', $request->faq_type_id);
            }

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $faqs = $query->select('id', 'faq_type_id', 'question', 'answers')
                ->orderBy('question', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQs retrieved successfully',
                'data' => $faqs
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve FAQs',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
