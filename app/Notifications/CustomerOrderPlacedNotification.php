<?php

namespace App\Notifications;

use App\Models\DonHang;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomerOrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DonHang $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Khách hàng đặt hàng mới',
            'message' => sprintf(
                'Đơn online #%d (%s) vừa được tạo và đang chờ xử lý.',
                $this->order->id,
                $this->order->ma_don_hang
            ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->ma_don_hang,
            'order_type' => $this->order->loai_don,
            'status' => $this->order->trang_thai_don,
        ];
    }
}
