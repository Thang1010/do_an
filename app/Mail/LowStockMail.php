<?php

namespace App\Mail;

use App\Models\NguyenLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $ingredient;
    public $currentStock;
    public $status;

    /**
     * Create a new message instance.
     */
    public function __construct(NguyenLieu $ingredient, float $currentStock, string $status)
    {
        $this->ingredient = $ingredient;
        $this->currentStock = $currentStock;
        $this->status = $status;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusStr = $this->status === 'het' ? 'HẾT HÀNG' : 'SẮP HẾT';
        return new Envelope(
            subject: "[Cảnh báo kho] Nguyên liệu {$this->ingredient->ten_nguyen_lieu} đang {$statusStr}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.inventory.low_stock',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
