<?php

namespace App\Mail;

use App\Models\CaLamViec;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AutoCheckoutMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $user,
        public CaLamViec $shift,
        public string $checkoutTime,
        public string $note,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hệ thống đã tự động chấm công ra cho bạn',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auto_checkout',
        );
    }
}
