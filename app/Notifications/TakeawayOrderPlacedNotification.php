<?php

namespace App\Notifications;

use App\Models\DonHang;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TakeawayOrderPlacedNotification extends Notification
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
        return [\App\Channels\CustomDatabaseChannel::class];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Đơn mang về mới',
            'message' => sprintf('Đơn mang về #%s vừa được khách đặt — vui lòng pha chế và đóng gói.', $this->order->ma_don_hang),
            'order_id' => $this->order->id,
            'order_code' => $this->order->ma_don_hang,
            'order_type' => 'mang về',
            'status' => $this->order->trang_thai_thanh_toan,
        ];
    }
}
