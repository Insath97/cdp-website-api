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
use App\Http\Requests\ReplyContactRequest;
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

    /**
     * Reply to the contact message.
     */
    public function reply(ReplyContactRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $contact = Contact::query()->find($id);

            if (!$contact) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Contact message not found'
                ], 404);
            }

            $contact->update([
                'reply_message' => $request->reply_message,
                'status'        => 'replied',
                'is_replied'    => true,
                'replied_by'    => Auth::id(),
                'replied_at'    => now(),
            ]);

            DB::commit();

            // Send reply email to visitor with try-catch and activity logging
            try {
                Mail::to($contact->email)->send(new ContactReplyMail($contact));
                $this->logActivity('MAIL_SENT', 'Contact', "Reply email sent to visitor: {$contact->email}");
            } catch (\Exception $e) {
                $this->logActivity('MAIL_FAILED', 'Contact', "Failed to send reply email to visitor: {$contact->email}. Error: " . $e->getMessage());
            }

            $this->logActivity('REPLY', 'Contact', "Replied to message from: {$contact->name}");

            return response()->json([
                'status'  => 'success',
                'message' => 'Reply recorded successfully',
                'data'    => $contact->load('repliedBy:id,name')
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to record reply',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error'
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
