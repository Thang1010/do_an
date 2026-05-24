<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerPasswordResetCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $expiresMinutes = 10
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('XM Coffee - Mã xác thực đặt lại mật khẩu')
            ->greeting('Xin chào!')
            ->line('Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản XM Coffee.')
            ->line('Mã xác thực của bạn là:')
            ->line($this->code)
            ->line('Mã xác thực có hiệu lực trong ' . $this->expiresMinutes . ' phút.')
            ->line('Nếu bạn không yêu cầu, vui lòng bỏ qua email này.');
    }
}
