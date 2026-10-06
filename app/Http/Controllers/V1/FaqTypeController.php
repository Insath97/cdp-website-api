<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FaqType;
use App\Http\Requests\CreateFaqTypeRequest;
use App\Http\Requests\UpdateFaqTypeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\ActivityLogTrait;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FaqTypeController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Faq Type Index', only: ['index', 'show']),
            new Middleware('permission:Faq Type Create', only: ['store']),
            new Middleware('permission:Faq Type Update', only: ['update']),
            new Middleware('permission:Faq Type Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Faq Type Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = FaqType::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $query->orderBy('created_at', 'desc');
            $faq_types = $query->paginate($perPage);

            $this->logActivity('INDEX', 'Faq Type', "Viewed faq types list");

            return response()->json([
                'status' => true,
                'message' => 'Faq types fetched successfully',
                'data' => $faq_types
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFaqTypeRequest $request)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();

            if (empty($validatedData['slug']) && !empty($validatedData['name'])) {
                $validatedData['slug'] = Str::slug($validatedData['name']);
            }

            $faq_type = FaqType::create($validatedData);

            DB::commit();

            $this->logActivity('CREATE', 'Faq Type', "Created faq type: {$faq_type->name}");

            return response()->json([
                'status' => true,
                'message' => 'Faq type created successfully',
                'data' => $faq_type
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create faq type',
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
            $faq_type = FaqType::query()->find($id);

            if (!$faq_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Faq type not found',
                    'data' => null
                ], 404);
            }

            $this->logActivity('SHOW', 'Faq Type', "Viewed faq type details: {$faq_type->name}", ['faq_type_id' => $faq_type->id]);

            return response()->json([
                'status' => true,
                'message' => 'Faq type fetched successfully',
                'data' => $faq_type
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFaqTypeRequest $request, string $id)
    {
        try {
            $faq_type = FaqType::query()->find($id);

            if (!$faq_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Faq type not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->all();

            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
            }

            $faq_type->update($data);

            DB::commit();

            $this->logActivity('UPDATE', 'Faq Type', "Updated faq type: {$faq_type->name}");

            return response()->json([
                'status' => true,
                'message' => 'Faq type updated successfully',
                'data' => $faq_type
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $faq_type = FaqType::find($id);

            if (!$faq_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Faq type not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $faqTypeName = $faq_type->name;
            $faq_type->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Faq Type', "Deleted faq type: {$faqTypeName}");

            return response()->json([
                'status' => true,
                'message' => 'Faq type deleted successfully',
                'data' => null
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
     * Toggle the status of the faq type.
     */
    public function toggleStatus(string $id)
    {
        try {
            $faq_type = FaqType::query()->find($id);

            if (!$faq_type) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Faq type not found',
                ], 404);
            }

            $faq_type->update(['is_active' => !$faq_type->is_active]);
            $status = $faq_type->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Faq Type', ucfirst($status) . " faq type: {$faq_type->name}");

            return response()->json([
                'status' => 'success',
                'message' => "Faq type {$status} successfully",
                'data' => $faq_type
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle faq type status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
