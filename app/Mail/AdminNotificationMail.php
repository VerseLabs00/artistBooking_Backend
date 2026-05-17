<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Notification $notification;


    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }


    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Platform Alert: ' . $this->notification->title,
        );
    }


    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_notification',
        );
    }


    public function attachments(): array
    {
        return [];
    }
}
