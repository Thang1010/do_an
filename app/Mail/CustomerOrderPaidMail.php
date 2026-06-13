<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Models\DonHang;

class CustomerOrderPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $customerName;

    /**
     * Create a new message instance.
     */
    public function __construct(DonHang $order)
    {
        $this->order = $order;
        $this->customerName = $order->nguoiDung?->ho_ten ?? 'Quý khách';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác nhận thanh toán đơn hàng ' . ($this->order->ma_don_hang ?? 'DON' . $this->order->id),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.customer_order_paid',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
