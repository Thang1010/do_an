<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerEmailVerificationNotification extends Notification
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
            ->subject('XM Coffee - Mã xác minh đăng ký')
            ->greeting('Xin chào!')
            ->line('Đây là mã xác minh đăng ký tài khoản của bạn:')
            ->line($this->code)
            ->line('Mã xác minh có hiệu lực trong ' . $this->expiresMinutes . ' phút.')
            ->line('Nếu bạn không yêu cầu, vui lòng bỏ qua email này.');
    }
}
