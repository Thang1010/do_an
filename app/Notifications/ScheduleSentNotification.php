<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleSentNotification extends Notification
{
    use Queueable;

    private string $fromDate;
    private string $toDate;

    public function __construct(string $fromDate, string $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function via(object $notifiable): array
    {
        return [\App\Channels\CustomDatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'schedule_sent',
            'title' => 'Lịch làm việc đã được gửi',
            'message' => "Lịch làm ngày {$this->fromDate} tới ngày {$this->toDate} đã được gửi về email của bạn.",
            'icon' => 'calendar',
            'url' => route('staff.shifts.index'),
        ];
    }
}
