<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormMail;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Http\Requests\StoreContactRequest;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\ContactSubmittedNotification;
use App\Traits\ActivityLogTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class PublicContactController extends Controller
{
    use ActivityLogTrait;

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
                        Mail::to($adminEmail)->send(new ContactFormMail([
                            'name' => $contact->first_name . ' ' . $contact->last_name,
                            'email' => $contact->email,
                            'subject' => $contact->subject,
                            'message' => $contact->message
                        ]));
                        $this->logActivity('MAIL_SENT', 'Contact', "Public contact notification email sent to admin: {$adminEmail}");
                    } catch (\Exception $mailException) {
                        $this->logActivity('MAIL_FAILED', 'Contact', "Failed to send public contact notification email to admin: {$adminEmail}. Error: " . $mailException->getMessage());
                    }
                }

                $adminUsers = User::role('Super Admin')->get();

                if ($adminUsers->isNotEmpty()) {
                    Notification::send($adminUsers, new ContactSubmittedNotification($contact));
                }
            }

            $this->logActivity('CREATE', 'Contact', "Submitted contact form from IP: {$contact->ip_address}");

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
