<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactType;
use App\Http\Requests\CreateContactTypeRequest;
use App\Http\Requests\UpdateContactTypeRequest;
use Illuminate\Support\Facades\DB;
use App\Traits\ActivityLogTrait;

class ContactTypeController extends Controller
{
    use ActivityLogTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            $perPage = $request->get('per_page', 15);
            $query = ContactType::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $query->orderBy('created_at', 'desc');
            $contact_types = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Contact types fetched successfully',
                'data' => $contact_types
            ], 200);

        }catch(\Throwable $th){
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
    public function store(CreateContactTypeRequest $request)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $contact_type = ContactType::create($validatedData);

            DB::commit();

            $this->logActivity('CREATE', 'Contact Type', "Created contact type: {$contact_type->name} ({$contact_type->code})");

            return response()->json([
                'status' => true,
                'message' => 'Contact type created successfully',
                'data' => $contact_type
            ], 201);

        } catch(\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create contact type',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{

            $contact_type = ContactType::query()->find($id);

            if (!$contact_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Contact type not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Contact type fetched successfully',
                'data' => $contact_type
            ], 200);

        }catch(\Throwable $th){
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
    public function update(UpdateContactTypeRequest $request, string $id)
    {
        try{
            $contact_type = ContactType::query()->find($id);

            if (!$contact_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Contact type not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $data = $request->all();
            $contact_type->update($data);

            DB::commit();

            $this->logActivity('UPDATE', 'Contact Type', "Updated contact type: {$contact_type->name}");

            return response()->json([
                'status' => true,
                'message' => 'Contact type updated successfully',
                'data' => $contact_type
            ], 200);

        }catch(\Throwable $th){
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
        try{
            $contact_type = ContactType::query()->find($id);

            if (!$contact_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Contact type not found',
                    'data' => null
                ], 404);
            }

            DB::beginTransaction();

            $ContactTypeName = $contact_type->name;
            $contact_type->query()->delete();

            DB::commit();

            $this->logActivity('DELETE', 'Contact Type', "Deleted contact_type: {$ContactTypeName}");

            return response()->json([
                'status' => true,
                'message' => 'Contact type deleted successfully',
                'data' => null
            ], 200);

        }catch(\Throwable $th){
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => null
            ], 500);
        }
    }

     /**
     * Activate the contact type.
     */
      public function activate(string $id)
    {
        try {
            $contact = ContactType::query()->find($id);

            if (!$contact) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contact not found',
                ], 404);
            }

            if ($contact->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contact is already active',
                ], 422);
            }

            $contact->update(['is_active' => true]);

            $this->logActivity('ACTIVATE', 'Contact', "Activated contact: {$contact->first_name} {$contact->last_name}");

            return response()->json([
                'status' => 'success',
                'message' => 'Contact activated successfully',
                'data' => $contact
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate contact',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Deactivate the contact.
     */

    public function deactivate(string $id)
    {
        try {
            $contact = ContactType::query()->find($id);

            if (! $contact) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contact not found',
                ], 404);
            }

            if (! $contact->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contact is already inactive',
                ], 422);
            }

            $contact->update(['is_active' => false]);

            $this->logActivity('DEACTIVATE', 'Contact', "Deactivated contact: {$contact->first_name} {$contact->last_name}");

            return response()->json([
                'status' => 'success',
                'message' => 'Contact deactivated successfully',
                'data' => $contact
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate contact',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
