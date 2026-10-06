<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\Faq;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FaqController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Faq Index', only: ['index', 'show']),
            new Middleware('permission:Faq Create', only: ['store']),
            new Middleware('permission:Faq Update', only: ['update']),
            new Middleware('permission:Faq Delete', only: ['destroy']),
            new Middleware('permission:Faq Toggle Active', only: ['toggleStatus']),
        ];
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Faq::with('faqType');

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('faq_type_id')) {
                $query->where('faq_type_id', $request->faq_type_id);
            }

            $query->orderBy('created_at', 'desc');
            $faqs = $query->paginate($perPage);

            $this->logActivity('INDEX', 'Faq', 'Retrieved FAQs listing');

            return response()->json([
                'status' => true,
                'message' => 'FAQs retrieved successfully',
                'data' => $faqs
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve FAQs',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function store(StoreFaqRequest $request)
    {
        $data = [];

        try {
            DB::beginTransaction();

            $data = $request->validated();

            $faq = Faq::create($data);

            DB::commit();

            $this->logActivity('CREATE', 'Faq', "Created FAQ: {$faq->question}", $data);

            return response()->json([
                'status' => true,
                'message' => 'FAQ created successfully',
                'data' => $faq->load('faqType')
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logActivity('CREATE_FAILED', 'Faq', "Failed to create FAQ: {$th->getMessage()}", $data);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create FAQ',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $faq = Faq::with('faqType')->find($id);

            if (!$faq) {
                return response()->json([
                    'status' => false,
                    'message' => 'FAQ not found',
                    'data' => null
                ], 404);
            }

            $this->logActivity('SHOW', 'Faq', "Retrieved FAQ details for ID: {$id}");

            return response()->json([
                'status' => true,
                'message' => 'FAQ retrieved successfully',
                'data' => $faq
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve FAQ',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function update(UpdateFaqRequest $request, string $id)
    {
        $data = [];

        try {
            $faq = Faq::find($id);

            if (!$faq) {
                return response()->json([
                    'status' => false,
                    'message' => 'FAQ not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();

            $faq->update($data);

            DB::commit();

            $this->logActivity('UPDATE', 'Faq', "Updated FAQ: {$faq->question}", $data);

            return response()->json([
                'status' => true,
                'message' => 'FAQ updated successfully',
                'data' => $faq->load('faqType')
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logActivity('UPDATE_FAILED', 'Faq', "Failed to update FAQ with ID: {$id}", ['error' => $th->getMessage(), 'data' => $data]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update FAQ',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $faq = Faq::find($id);

            if (!$faq) {
                return response()->json([
                    'status' => false,
                    'message' => 'FAQ not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $faqQuestion = $faq->question;
            $faq->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Faq', "Deleted FAQ: {$faqQuestion}");

            return response()->json([
                'status' => true,
                'message' => 'FAQ deleted successfully',
                'data' => null
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logActivity('DELETE_FAILED', 'Faq', "Failed to delete FAQ with ID: {$id}", ['error' => $th->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete FAQ',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function toggleStatus(string $id)
    {
        try {
            $faq = Faq::find($id);

            if (!$faq) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'FAQ not found',
                    'data' => null
                ], 404);
            }

            $faq->is_active = !$faq->is_active;
            $faq->save();

            $this->logActivity('TOGGLE_STATUS', 'Faq', "Toggled FAQ status: {$faq->question} (" . ($faq->is_active ? 'Active' : 'Inactive') . ")");

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ status updated successfully',
                'data' => [
                    'id' => $faq->id,
                    'is_active' => $faq->is_active
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle FAQ status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
