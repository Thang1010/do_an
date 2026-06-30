<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Hoá đơn TỔNG của một bàn gửi về email nhân viên nhập khi thanh toán:
 * liệt kê mọi món đã thanh toán (phần khách QR trả trước + phần vừa thu),
 * tách rõ "Đã thanh toán trước" và "Thu lần này" để khách không nhầm tính trùng.
 */
class TableInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, array{ten:string, size:?string, sl:int, thanh_tien:float, ghi_chu:?string, paid_now:bool}> $lines
     */
    public function __construct(
        public string $soBan,
        public array $lines,
        public float $paidBefore,
        public float $payNow,
        public float $grandTotal,
        public ?string $method,
        public string $thoiGian,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hoá đơn thanh toán - Bàn ' . $this->soBan,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.table_invoice',
        );
    }
}
