<?php

namespace App\Notifications;

use App\Models\CuaHang;
use App\Models\NguoiDung;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingAccountApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly NguoiDung $pendingUser,
        private readonly ?CuaHang $store,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [\App\Channels\CustomDatabaseChannel::class, 'mail'];
    }

    /**
     * Email gửi tới người duyệt (chủ cửa hàng / quản lý) khi có tài khoản chờ duyệt.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $storeName = $this->store?->ten_cua_hang ?? 'XM Coffee';

        return (new MailMessage())
            ->subject('Yêu cầu duyệt tài khoản mới — ' . $storeName)
            ->greeting('Xin chào,')
            ->line(sprintf(
                '%s (%s) vừa đăng ký với vai trò "%s" và đang chờ bạn xác nhận.',
                $this->pendingUser->ho_ten,
                $this->pendingUser->email ?? '—',
                $this->pendingUser->vai_tro
            ))
            ->action('Xem yêu cầu chờ duyệt', route('manager.users.pending-approvals'))
            ->line('Vui lòng đăng nhập để xác nhận hoặc từ chối tài khoản này.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $targetUrl = route('manager.users.pending-approvals');

        return [
            'title' => 'Yêu cầu đăng ký tài khoản mới',
            'message' => sprintf(
                '%s đăng ký vai trò %s và đang chờ xác nhận.',
                $this->pendingUser->ho_ten,
                $this->pendingUser->vai_tro
            ),
            'user_id' => $this->pendingUser->id,
            'user_name' => $this->pendingUser->ho_ten,
            'user_email' => $this->pendingUser->email,
            'requested_role' => $this->pendingUser->vai_tro,
            'store_id' => $this->store?->id,
            'store_name' => $this->store?->ten_cua_hang,
            'target_url' => $targetUrl,
        ];
    }
}
