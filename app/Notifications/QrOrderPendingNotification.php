<?php

namespace App\Notifications;

use App\Models\DonHang;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QrOrderPendingNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DonHang $order)
    {
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
            'title' => 'Đơn QR mới chờ xác nhận',
            'message' => sprintf('Đơn #%d (%s) vừa được tạo từ QR và đang chờ xác nhận.', $this->order->id, $this->order->ma_don_hang),
            'order_id' => $this->order->id,
            'order_code' => $this->order->ma_don_hang,
            'order_type' => $this->order->loai_don,
            'status' => $this->order->trang_thai_don,
            'target_url' => route('manager.orders.show', ['id' => $this->order->id]),
        ];
    }
}
