<?php

namespace App\Mail;

use App\Models\DonHang;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(DonHang $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        $orderCode = $this->order->ma_don_hang ?? ('DON' . $this->order->id);
        return new Envelope(
            subject: "Đơn hàng {$orderCode} đã thanh toán thành công",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_paid',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
