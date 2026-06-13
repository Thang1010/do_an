<?php

namespace App\Notifications;

use App\Models\CaLamViec;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShiftAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly CaLamViec $shift)
    {
    }

    public function via(object $notifiable): array
    {
        return [\App\Channels\CustomDatabaseChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $shiftDate = optional($this->shift->ngay_lam)->format('d/m/Y');

        return [
            'title' => 'Bạn được phân công ca làm việc',
            'message' => sprintf(
                'Ca %s ngày %s (%s - %s) đã được phân công cho bạn.',
                $this->shift->ten_ca,
                $shiftDate ?: '—',
                $this->shift->gio_bat_dau,
                $this->shift->gio_ket_thuc
            ),
            'shift_id' => $this->shift->id,
            'shift_name' => $this->shift->ten_ca,
            'shift_date' => $this->shift->ngay_lam,
            'start_time' => $this->shift->gio_bat_dau,
            'end_time' => $this->shift->gio_ket_thuc,
            'target_url' => route('staff.shifts.show', ['id' => $this->shift->id]),
        ];
    }
}
