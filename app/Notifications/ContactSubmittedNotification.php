<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContactSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public Contact $contact)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'contact_submitted',
            'title' => 'New contact submission',
            'message' => $this->contact->subject,
            'contact_id' => $this->contact->id,
            'contact_name' => trim($this->contact->first_name . ' ' . $this->contact->last_name),
            'email' => $this->contact->email,
            'phone' => $this->contact->phone ?? null,
            'created_at' => $this->contact->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}