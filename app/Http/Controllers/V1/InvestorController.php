<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateInvestorRequest;
use App\Http\Requests\UpdateInvestorRequest;
use App\Models\Investor;
use App\Models\InvestorQualification;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InvestorController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Investor Index', only: ['index', 'show']),
            new Middleware('permission:Investor Create', only: ['store']),
            new Middleware('permission:Investor Update', only: ['update']),
            new Middleware('permission:Investor Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Investor Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Investor::with(['qualifications']);

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
            $investors = $query->paginate($perPage);

            $this->logActivity('INDEX', 'Investor', "Viewed investors list");

            return response()->json([
                'status' => true,
                'message' => 'Investors fetched successfully',
                'data' => $investors
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
    public function store(CreateInvestorRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $qualifications = $data['qualifications'] ?? [];
            unset($data['qualifications']);

            // Handle image upload
            if ($request->hasFile('image')) {
                $filepath = $this->handleFileUpload($request, 'image', null, 'investors', Str::slug($data['name']));
                if ($filepath) {
                    $data['image'] = $filepath;
                }
            }

            $investor = Investor::create($data);

            $this->syncQualifications($investor, $qualifications);

            DB::commit();

            $this->logActivity('CREATE', 'Investor', "Created investor: {$investor->name}");

            $investor->load(['qualifications']);

            return response()->json([
                'status' => true,
                'message' => 'Investor created successfully',
                'data' => $investor
            ], 201);

        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create investor',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create investor',
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
            $investor = Investor::with(['qualifications'])->find($id);

            if (!$investor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Investor not found',
                    'data' => null
                ], 404);
            }

            $this->logActivity('SHOW', 'Investor', "Viewed investor details: {$investor->name}", ['investor_id' => $investor->id]);

            return response()->json([
                'status' => true,
                'message' => 'Investor fetched successfully',
                'data' => $investor
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
    public function update(UpdateInvestorRequest $request, string $id)
    {
        try {
            $investor = Investor::with(['qualifications'])->find($id);

            if (!$investor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Investor not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $qualifications = $data['qualifications'] ?? null;
            unset($data['qualifications']);

            // Handle image swap (old file deleted by trait)
            if ($request->hasFile('image')) {
                $filepath = $this->handleFileUpload(
                    $request,
                    'image',
                    $investor->image,
                    'investors',
                    Str::slug($data['name'] ?? $investor->name)
                );
                if ($filepath) {
                    $data['image'] = $filepath;
                }
            }

            $investor->update($data);

            // Replace qualifications only when the key is sent
            if ($request->has('qualifications')) {
                $investor->qualifications()->delete();
                $this->syncQualifications($investor, $qualifications ?? []);
            }

            DB::commit();

            $this->logActivity('UPDATE', 'Investor', "Updated investor: {$investor->name}");

            $investor->load(['qualifications']);

            return response()->json([
                'status' => true,
                'message' => 'Investor updated successfully',
                'data' => $investor
            ], 200);

        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update investor',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update investor',
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
            $investor = Investor::find($id);

            if (!$investor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Investor not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $investorName = $investor->name;

            // Delete actual file (qualifications removed by DB cascade)
            $this->deleteFile($investor->image);

            $investor->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Investor', "Deleted investor: {$investorName}");

            return response()->json([
                'status' => true,
                'message' => 'Investor deleted successfully',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete investor',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle the status of the investor.
     */
    public function toggleStatus(string $id)
    {
        try {
            $investor = Investor::find($id);

            if (!$investor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Investor not found',
                    'data' => null
                ], 404);
            }

            $investor->update(['is_active' => !$investor->is_active]);
            $status = $investor->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Investor', ucfirst($status) . " investor: {$investor->name}");

            return response()->json([
                'status' => 'success',
                'message' => "Investor {$status} successfully",
                'data' => $investor
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle investor status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Persist qualification rows for the investor.
     */
    private function syncQualifications(Investor $investor, array $titles): void
    {
        foreach (array_values($titles) as $index => $title) {
            InvestorQualification::create([
                'investor_id' => $investor->id,
                'title' => $title,
                'sort_order' => $index,
            ]);
        }
    }
}
