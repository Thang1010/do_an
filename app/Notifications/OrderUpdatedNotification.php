<?php

namespace App\Notifications;

use App\Models\DonHang;
use App\Models\NguoiDung;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly DonHang $order,
        private readonly ?NguoiDung $actor,
    ) {
    }

    public function via(object $notifiable): array
    {
        return [\App\Channels\CustomDatabaseChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $actorName = $this->actor?->ho_ten ?? 'Nhân viên';

        return [
            'title' => 'Đơn hàng được cập nhật',
            'message' => sprintf(
                'Đơn #%d (%s) đã được %s cập nhật.',
                $this->order->id,
                $this->order->ma_don_hang,
                $actorName
            ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->ma_don_hang,
            'updated_by' => $actorName,
            'target_url' => route('customer.orders'),
        ];
    }
}
