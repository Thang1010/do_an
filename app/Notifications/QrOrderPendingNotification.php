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
        return [\App\Channels\CustomDatabaseChannel::class];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $tableNumber = $this->order->banAn?->so_ban;
        $tableLabel = $tableNumber ? ('Bàn ' . $tableNumber) : 'Đơn';

        return [
            'title' => 'Đơn QR mới',
            'message' => sprintf('%s #%d (%s) vừa được tạo từ QR.', $tableLabel, $this->order->id, $this->order->ma_don_hang),
            'order_id' => $this->order->id,
            'order_code' => $this->order->ma_don_hang,
            'order_type' => $this->order->loai_don,
            'status' => $this->order->trang_thai_thanh_toan,
            'table_id' => $this->order->ban_an_id,
            'table_number' => $tableNumber,
        ];
    }
}
