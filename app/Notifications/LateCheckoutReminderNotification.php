<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateCheckoutReminderNotification extends Notification
{
    use Queueable;

    public $shift;

    public function __construct($shift)
    {
        $this->shift = $shift;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nhắc nhở Checkout ca làm việc')
            ->greeting('Xin chào ' . ($notifiable->ho_ten ?? 'bạn') . ',')
            ->line('Ca làm việc "' . $this->shift->ten_ca . '" của bạn đã kết thúc cách đây hơn 5 phút.')
            ->line('Vui lòng truy cập hệ thống để thực hiện Checkout.')
            ->action('Đăng nhập và Checkout', url('/login'))
            ->line('Xin cảm ơn!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ca_lam_viec_id' => $this->shift->id,
            'message' => 'Bạn chưa checkout ca ' . $this->shift->ten_ca,
        ];
    }
}
