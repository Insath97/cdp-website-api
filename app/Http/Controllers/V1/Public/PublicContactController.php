<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactReplyMail;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Http\Requests\StoreContactRequest;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\ContactSubmittedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class PublicContactController extends Controller
{
    /**
     * Submit a new contact message from the public website.
     */
    public function store(StoreContactRequest $request)
    {
        try {
            $data = $request->validated();

            // Capture the user's IP address
            $data['ip_address'] = $request->ip();

            // Store in database
            $contact = Contact::create($data);

            if (SystemSetting::getValue('enable_contact_notification', '1') == '1') {
                $adminEmail = SystemSetting::getValue('contact_notification_email', config('mail.from.address'));

                if ($adminEmail) {
                    try {
                        Mail::to($adminEmail)->send(new ContactReplyMail(
                            $contact,
                            'New Contact Enquiry: ' . $contact->subject,
                            $contact->message
                        ));
                    } catch (\Exception $mailException) {
                        Log::error('Public contact notify mail error: ' . $mailException->getMessage());
                    }
                }

                $adminUsers = User::role('Super Admin')->get();

                if ($adminUsers->isNotEmpty()) {
                    Notification::send($adminUsers, new ContactSubmittedNotification($contact));
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Your message has been sent successfully. We will get back to you soon!',
                'data' => $contact
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to submit message',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
