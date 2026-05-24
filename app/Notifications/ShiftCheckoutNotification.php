<?php

namespace App\Notifications;

use App\Models\CaLamViec;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShiftCheckoutNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly CaLamViec $shift,
        private readonly string $note
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $shiftDate = optional($this->shift->ngay_lam)->format('d/m/Y');
        $noteText = trim($this->note) !== '' ? $this->note : 'Đúng giờ';

        return [
            'title' => 'Check-out thành công',
            'message' => sprintf(
                'Bạn đã check-out ca %s ngày %s. Ghi chú: %s.',
                $this->shift->ten_ca,
                $shiftDate ?: '—',
                $noteText
            ),
            'shift_id' => $this->shift->id,
            'shift_name' => $this->shift->ten_ca,
            'shift_date' => $this->shift->ngay_lam,
            'note' => $noteText,
            'target_url' => route('staff.shifts.show', ['id' => $this->shift->id]),
        ];
    }
}
