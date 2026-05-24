<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\DonHang;
use App\Models\NguoiDung;
use App\Notifications\CustomerOrderCancelledNotification;
use App\Notifications\CustomerOrderPlacedNotification;
use App\Notifications\QrOrderPendingNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class OrderNotificationService
{
    public static function notifyCustomerUpdated(DonHang $order): void
    {
        $customerName = self::resolveCustomerName($order);
        $title = 'Đơn hàng được khách cập nhật';
        $message = sprintf(
            'Đơn #%d (%s) vừa được %s cập nhật.',
            $order->id,
            $order->ma_don_hang,
            $customerName
        );

        self::refreshPendingOrderNotification($order, $title, $message);
    }

    public static function notifyCustomerCancelled(DonHang $order): void
    {
        $customerName = self::resolveCustomerName($order);
        $title = 'Đơn hàng đã bị khách hủy';
        $message = sprintf(
            'Đơn #%d (%s) đã được %s hủy.',
            $order->id,
            $order->ma_don_hang,
            $customerName
        );

        $updatedCount = self::refreshPendingOrderNotification($order, $title, $message);
        if ($updatedCount > 0) {
            return;
        }

        if (! Schema::hasTable('notifications')) {
            return;
        }

        $recipients = self::staffRecipients();
        if ($recipients->isEmpty()) {
            return;
        }

        $recipients->each->notify(new CustomerOrderCancelledNotification($order));
    }

    public static function refreshPendingOrderNotification(DonHang $order, string $title, string $message): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        $updatedCount = 0;
        $recipients = self::staffRecipients();

        foreach ($recipients as $user) {
            $notification = self::findPendingOrderNotification($user, $order->id);
            if (! $notification) {
                continue;
            }

            $data = (array) ($notification->data ?? []);
            $data['title'] = $title;
            $data['message'] = $message;
            $data['status'] = $order->trang_thai_don;
            $data['customer_name'] = self::resolveCustomerName($order);

            $notification->forceFill([
                'data' => $data,
                'read_at' => null,
                'created_at' => now(),
            ])->save();

            $updatedCount++;
        }

        return $updatedCount;
    }

    private static function staffRecipients(): Collection
    {
        return NguoiDung::query()
            ->whereIn('vai_tro', UserRole::staffRoleValues())
            ->where('trang_thai', UserStatus::HOAT_DONG->value)
            ->get();
    }

    private static function findPendingOrderNotification(NguoiDung $user, int $orderId): ?DatabaseNotification
    {
        $notifications = $user->notifications()
            ->whereIn('type', [
                CustomerOrderPlacedNotification::class,
                QrOrderPendingNotification::class,
            ])
            ->latest()
            ->get();

        foreach ($notifications as $notification) {
            $data = (array) ($notification->data ?? []);
            if ((int) ($data['order_id'] ?? 0) === $orderId) {
                return $notification;
            }
        }

        return null;
    }

    private static function resolveCustomerName(DonHang $order): string
    {
        return $order->nguoiDung?->ho_ten
            ?? $order->ten_khach_hang
            ?? 'Khách hàng';
    }
}
