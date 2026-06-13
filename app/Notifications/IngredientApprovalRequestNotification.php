<?php

namespace App\Notifications;

use App\Models\NguoiDung;
use App\Models\YeuCauNguyenLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IngredientApprovalRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly YeuCauNguyenLieu $request,
        private readonly NguoiDung $requester,
    ) {
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
        $items = (array) ($this->request->du_lieu ?? []);
        $totalItems = count($items);

        return [
            'title' => 'Yêu cầu thêm nguyên liệu mới',
            'message' => sprintf('%s đã gửi %d nguyên liệu chờ xác nhận.', $this->requester->ho_ten, $totalItems),
            'request_id' => $this->request->id,
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->ho_ten,
            'store_id' => $this->request->cua_hang_id,
            'target_url' => route('manager.ingredients.requests.show', ['id' => $this->request->id]),
        ];
    }
}
