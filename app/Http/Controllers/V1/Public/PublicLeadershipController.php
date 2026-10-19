<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\LeadershipMember;
use Illuminate\Http\Request;

class PublicLeadershipController extends Controller
{
    private const LIST_COLUMNS = [
        'id', 'section', 'name', 'designation', 'group_label', 'summary',
        'message', 'focus_area', 'linkedin', 'photo', 'sort_order',
    ];

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LeadershipMember::active()
                ->with('qualifications:id,leadership_member_id,title,sort_order')
                ->select(self::LIST_COLUMNS);

            if ($request->has('section') && $request->section != '') {
                $query->section($request->section);
            }

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $members = $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => $members->isEmpty()
                    ? 'No leadership members found.'
                    : 'Leadership members retrieved successfully',
                'data' => $members,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve leadership members',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(Request $request, string $id)
    {
        try {
            $member = LeadershipMember::active()
                ->with('qualifications:id,leadership_member_id,title,sort_order')
                ->find($id);

            if (! $member) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Leadership member not found',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Leadership member retrieved successfully',
                'data' => $member,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve leadership member',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
