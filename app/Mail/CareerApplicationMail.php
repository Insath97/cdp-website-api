<?php

namespace App\Mail;

use App\Models\CareerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareerApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;

    /**
     * Create a new message instance.
     */
    public function __construct(CareerApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $jobTitle = $this->application->career ? $this->application->career->title : 'Unknown Position';
        return new Envelope(
            subject: "New Job Application: {$this->application->fullname} - {$jobTitle} [{$this->application->application_code}]",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.career-application',
            with: [
                'application' => $this->application,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        if ($this->application->resume_path) {
            $path = public_path($this->application->resume_path);
            if (file_exists($path)) {
                return [
                    Attachment::fromPath($path)
                        ->as(basename($path))
                ];
            }
        }
        return [];
    }
}
