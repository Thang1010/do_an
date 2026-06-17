<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShiftUpdatedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return [\App\Channels\CustomDatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'shift_updated',
            'title' => 'Ca làm việc đã thay đổi',
            'message' => 'Đã có thay đổi ca làm việc và đã được gửi về email của bạn.',
            'icon' => 'calendar',
            'url' => route('staff.shifts.index'),
        ];
    }
}
