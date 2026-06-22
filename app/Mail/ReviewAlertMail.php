<?php

namespace App\Mail;

use App\Models\DanhGiaSanPham;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param DanhGiaSanPham $review Đánh giá cần lưu ý
     * @param string $camXuc 'Tiêu cực' hoặc 'Trung lập'
     */
    public function __construct(
        public DanhGiaSanPham $review,
        public string $camXuc,
    ) {
    }

    public function envelope(): Envelope
    {
        $sanPham = $this->review->sanPham?->ten_san_pham ?? 'sản phẩm';

        return new Envelope(
            subject: "[Đánh giá {$this->camXuc}] cần lưu ý — {$sanPham}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.review_alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
