<?php

namespace App\Mail;

use App\Models\Inventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Inventory $item,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'RAM-CIMS Inventory Alert: Low Stock',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.low-stock-alert',
        );
    }
}
