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

    public string $detectedAt;

    public function __construct(
        public Inventory $item,
        public string $status,
    ) {
        $this->status = str_contains(strtoupper($status), 'OUT') ? 'OUT OF STOCK' : 'LOW STOCK';
        $this->detectedAt = now()->timezone(config('app.timezone'))->format('F j, Y g:i A T');
    }

    public function alertLabel(): string
    {
        return str_contains(strtoupper($this->status), 'OUT') ? 'OUT OF STOCK' : 'LOW STOCK';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'RAM-CIMS Inventory Alert: '.$this->alertLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.low-stock-alert-html',
            text: 'mail.low-stock-alert',
        );
    }
}
