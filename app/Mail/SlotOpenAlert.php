<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlotOpenAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $physician,
        public string $when,
        public string $appointmentsUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'APC Clinic appointment slot is open',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.slot-open',
        );
    }
}
