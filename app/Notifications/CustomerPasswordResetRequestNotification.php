<?php

namespace App\Notifications;

use App\Models\CuaHang;
use App\Models\NguoiDung;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomerPasswordResetRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly NguoiDung $customer,
        private readonly ?NguoiDung $requester,
        private readonly ?CuaHang $store,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $requesterName = $this->requester?->ho_ten ?? 'Nhân viên';

        return [
            'title' => 'Yêu cầu cấp lại mật khẩu khách hàng',
            'message' => sprintf(
                '%s đề nghị cấp lại mật khẩu cho khách hàng %s.',
                $requesterName,
                $this->customer->ho_ten
            ),
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->ho_ten,
            'customer_phone' => $this->customer->so_dien_thoai,
            'store_id' => $this->store?->id,
            'store_name' => $this->store?->ten_cua_hang,
            'requested_by' => $requesterName,
            'target_url' => route('manager.users.customers'),
        ];
    }
}
