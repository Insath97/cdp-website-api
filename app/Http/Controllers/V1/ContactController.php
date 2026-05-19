<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use App\Mail\ContactReplyMail;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\CreateContactRequest;
use App\Http\Requests\UpdateContactRequest;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    use ActivityLogTrait;

    public function index(Request $request)
    {
        try {

            $perPage = $request->get('per_page', 15);
            $query = Contact::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            //Filters
            $query->orderBy('created_at', 'desc');
            $contacts = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Contacts fetched successfully',
                'data' => $contacts
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => null
            ], 500);
        }
    }

    public function store(StoreContactRequest $request)
    {
        try {

            DB::beginTransaction();

            $data = $request->validated();
            $contact = Contact::create($data);

            try {
                Mail::to('dev@localhost.com')->send(new ContactReplyMail(
                    $contact,
                    'New Contact Enquiry: ' . $contact->subject,
                    $contact->message
                ));
            } catch (\Exception $mailException) {
                Log::error('Contact notify mail error: ' . $mailException->getMessage());
            }

            DB::commit();

            $this->logActivity('CREATE', 'Contact', "Created contact: {$contact->first_name} {$contact->last_name}");

            return response()->json([
                'status' => true,
                'message' => 'Contact created successfully',
                'data' => $contact
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => null
            ], 500);
        }


    }


    /**
     * Send reply email to a contact and store reply metadata.
     */
    public function sendEmail(CreateContactRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            $contact = Contact::query()->find($id);

            if (! $contact) {
                return response()->json(['status' => 'error', 'message' => 'Contact not found'], 404);
            }

            $contact->update([
                'reply' => $data['message'],
                'is_replied' => true,
                'replied_by' => Auth::id(),
                'replied_at' => now(),
                'status' => 'replied',
            ]);

            try {
                Mail::to('dev@localhost.com')->send(new ContactReplyMail(
                    $contact,
                    $data['subject'],
                    $data['message']
                ));
            } catch (\Exception $mailException) {
                Log::error('Contact Mail Error: ' . $mailException->getMessage());
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Your message has been received and stored. We will get back to you soon.'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process your request. Please try again later.',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $contact = Contact::query()->find($id);
            if (! $contact) {
                return response()->json(['status' => 'error', 'message' => 'Contact not found'], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Contact fetched successfully',
                'data' => $contact
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => null
            ], 500);
        }
    }

        public function destroy(string $id)
    {
        try {
            $contact = Contact::query()->find($id);
            if (! $contact) {
                return response()->json(['status' => 'error', 'message' => 'Contact not found'], 404);
            }

            $contact->query()->delete();

            $this->logActivity('DELETE', 'Contact', "Deleted contact: {$contact->first_name} {$contact->last_name}");

            return response()->json([
                'status' => true,
                'message' => 'Contact deleted successfully',
                'data' => null
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
     * Activate the contact.
     */
    public function activate(string $id)
    {
        try {
            $contact = Contact::query()->find($id);

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
            $contact = Contact::query()->find($id);

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
