<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeadershipMemberRequest;
use App\Http\Requests\UpdateLeadershipMemberRequest;
use App\Models\LeadershipMember;
use App\Models\LeadershipQualification;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LeadershipController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Leadership Index', only: ['index', 'show']),
            new Middleware('permission:Leadership Create', only: ['store']),
            new Middleware('permission:Leadership Update', only: ['update']),
            new Middleware('permission:Leadership Toggle Active', only: ['toggleStatus']),
            new Middleware('permission:Leadership Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LeadershipMember::with(['qualifications']);

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('section')) {
                $query->section($request->section);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
            $members = $query->paginate($perPage);

            $this->logActivity('INDEX', 'Leadership', "Viewed leadership members list");

            return response()->json([
                'status' => true,
                'message' => 'Leadership members fetched successfully',
                'data' => $members
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
    public function store(CreateLeadershipMemberRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $qualifications = $data['qualifications'] ?? [];
            unset($data['qualifications']);

            // Handle portrait upload
            if ($request->hasFile('photo')) {
                $filepath = $this->handleFileUpload($request, 'photo', null, 'leadership', Str::slug($data['name']));
                if ($filepath) {
                    $data['photo'] = $filepath;
                }
            }

            $member = LeadershipMember::create($data);

            $this->syncQualifications($member, $qualifications);

            DB::commit();

            $this->logActivity('CREATE', 'Leadership', "Created leadership member: {$member->name}");

            $member->load(['qualifications']);

            return response()->json([
                'status' => true,
                'message' => 'Leadership member created successfully',
                'data' => $member
            ], 201);

        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create leadership member',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create leadership member',
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
            $member = LeadershipMember::with(['qualifications'])->find($id);

            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Leadership member not found',
                    'data' => null
                ], 404);
            }

            $this->logActivity('SHOW', 'Leadership', "Viewed leadership member details: {$member->name}", ['leadership_member_id' => $member->id]);

            return response()->json([
                'status' => true,
                'message' => 'Leadership member fetched successfully',
                'data' => $member
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
    public function update(UpdateLeadershipMemberRequest $request, string $id)
    {
        try {
            $member = LeadershipMember::with(['qualifications'])->find($id);

            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Leadership member not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $qualifications = $data['qualifications'] ?? null;
            unset($data['qualifications']);

            // Handle portrait swap (old file deleted by trait)
            if ($request->hasFile('photo')) {
                $filepath = $this->handleFileUpload(
                    $request,
                    'photo',
                    $member->photo,
                    'leadership',
                    Str::slug($data['name'] ?? $member->name)
                );
                if ($filepath) {
                    $data['photo'] = $filepath;
                }
            }

            $member->update($data);

            // Replace children only when the keys are sent
            if ($request->has('qualifications')) {
                $member->qualifications()->delete();
                $this->syncQualifications($member, $qualifications ?? []);
            }

            DB::commit();

            $this->logActivity('UPDATE', 'Leadership', "Updated leadership member: {$member->name}");

            $member->load(['qualifications']);

            return response()->json([
                'status' => true,
                'message' => 'Leadership member updated successfully',
                'data' => $member
            ], 200);

        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update leadership member',
                'error' => config('app.debug') ? $qe->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update leadership member',
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
            $member = LeadershipMember::find($id);

            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Leadership member not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $memberName = $member->name;

            // Delete actual file (children removed by cascade)
            $this->deleteFile($member->photo);

            $member->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Leadership', "Deleted leadership member: {$memberName}");

            return response()->json([
                'status' => true,
                'message' => 'Leadership member deleted successfully',
                'data' => null
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete leadership member',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle the status of the leadership member.
     */
    public function toggleStatus(string $id)
    {
        try {
            $member = LeadershipMember::find($id);

            if (!$member) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Leadership member not found',
                    'data' => null
                ], 404);
            }

            $member->update(['is_active' => !$member->is_active]);
            $status = $member->is_active ? 'activated' : 'deactivated';

            $this->logActivity('TOGGLE_STATUS', 'Leadership', ucfirst($status) . " leadership member: {$member->name}");

            return response()->json([
                'status' => 'success',
                'message' => "Leadership member {$status} successfully",
                'data' => $member
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle leadership member status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Persist qualification rows for the member.
     */
    private function syncQualifications(LeadershipMember $member, array $titles): void
    {
        foreach (array_values($titles) as $index => $title) {
            LeadershipQualification::create([
                'leadership_member_id' => $member->id,
                'title' => $title,
                'sort_order' => $index,
            ]);
        }
    }
}
