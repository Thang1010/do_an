<?php

namespace App\Notifications;

use App\Models\DonHang;
use App\Models\ThanhToan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QrPaymentSuccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly DonHang $order,
        private readonly ThanhToan $payment,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Thanh toán QR thành công',
            'message' => sprintf(
                'Đơn #%d (%s) đã được thanh toán thành công qua QR.',
                $this->order->id,
                $this->order->ma_don_hang
            ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->ma_don_hang,
            'payment_id' => $this->payment->id,
            'payment_method' => $this->payment->phuong_thuc,
            'payment_status' => $this->payment->trang_thai,
            'payment_amount' => (float) ($this->payment->so_tien ?? 0),
            'paid_at' => optional($this->payment->thanh_toan_luc ?? now())->toDateTimeString(),
                'target_url' => route('manager.orders.show', ['id' => $this->order->id]),
        ];
    }
}
