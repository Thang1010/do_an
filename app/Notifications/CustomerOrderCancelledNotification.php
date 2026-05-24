<?php

namespace App\Notifications;

use App\Models\DonHang;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomerOrderCancelledNotification extends Notification
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
        $customerName = $this->order->nguoiDung?->ho_ten
            ?? $this->order->ten_khach_hang
            ?? 'Khách hàng';

        return [
            'title' => 'Khách hàng đã hủy đơn',
            'message' => sprintf(
                'Đơn #%d (%s) đã được %s hủy.',
                $this->order->id,
                $this->order->ma_don_hang,
                $customerName
            ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->ma_don_hang,
            'order_type' => $this->order->loai_don,
            'status' => $this->order->trang_thai_don,
            'customer_name' => $customerName,
            'target_url' => route('manager.orders.show', ['id' => $this->order->id]),
        ];
    }
}
