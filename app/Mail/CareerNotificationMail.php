<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareerNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $career;

    /**
     * Create a new message instance.
     */
    public function __construct($career)
    {
        $this->career = $career;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Career Post Created: ' . $this->career->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.career-notification',
            with: [
                'title' => $this->career->title,
                'slug' => $this->career->slug,
                'description' => $this->career->description,
                'poster_image' => $this->career->poster_image,
                'department' => $this->career->department,
                'location' => $this->career->location,
                'job_type' => $this->career->job_type,
                'due_date' => $this->career->due_date,
                'key_responsibilities' => $this->career->key_responsibilities ?? null,
                'requirements' => $this->career->requirements ?? null,
                'benefits' => $this->career->benefits ?? null,
                'is_active' => $this->career->is_active ?? null,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
