<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShiftDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $tenCa,
        private string $ngayLam,
    ) {}

    public function via(object $notifiable): array
    {
        return [\App\Channels\CustomDatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'shift_deleted',
            'title' => 'Ca làm việc đã bị hủy',
            'message' => "Ca \"{$this->tenCa}\" ngày {$this->ngayLam} đã bị hủy và đã được gửi về email của bạn.",
            'icon' => 'calendar',
            'url' => route('staff.shifts.index'),
        ];
    }
}
