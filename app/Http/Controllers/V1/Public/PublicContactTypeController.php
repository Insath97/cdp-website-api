<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactType;

class PublicContactTypeController extends Controller
{
    /**
     * Get active Contact Types list.
     */
    public function index(Request $request)
    {
        try {
            $contactTypes = ContactType::active()
                ->orderBy('created_at', 'asc')
                ->get(['id', 'name', 'code']);

            return response()->json([
                'status' => 'success',
                'message' => $contactTypes->isEmpty() 
                    ? "No contact types found." 
                    : "Contact types retrieved successfully",
                'data' => $contactTypes
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Contact Types',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
