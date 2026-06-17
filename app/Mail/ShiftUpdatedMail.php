<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $user,
        public array $oldShift,
        public array $newShift,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thông báo thay đổi ca làm việc',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shift_updated',
        );
    }
}
