<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;

class PublicInvestorController extends Controller
{
    private const LIST_COLUMNS = [
        'id', 'name', 'designation', 'organization', 'image', 'sort_order',
    ];

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Investor::active()
                ->with('qualifications:id,investor_id,title,sort_order')
                ->select(self::LIST_COLUMNS);

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $investors = $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => $investors->isEmpty()
                    ? 'No investors found.'
                    : 'Investors retrieved successfully',
                'data' => $investors,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve investors',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(Request $request, string $id)
    {
        try {
            $investor = Investor::active()
                ->with('qualifications:id,investor_id,title,sort_order')
                ->find($id);

            if (! $investor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Investor not found',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Investor retrieved successfully',
                'data' => $investor,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve investor',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
