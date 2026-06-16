<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Form Submission from ' . $this->data['first_name'] . ' ' . $this->data['last_name'],
            replyTo: [
                $this->data['email']
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact_form',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
