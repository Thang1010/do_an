<?php

namespace App\Services;

use App\Enums\TableStatus;
use App\Models\BanAn;
use App\Models\DonHang;

class TableStatusService
{
    private const BOOKING_NOTE_PREFIX = 'Hẹn đến lúc:';

    public static function isBookingOrder(DonHang $order): bool
    {
        return $order->loai_don === 'đặt hàng trước';
    }

    public static function isBookingNote(?string $note): bool
    {
        // Legacy check kept for compatibility but ghi_chu was removed from don_hang
        if (!$note) {
            return false;
        }

        return str_contains($note, self::BOOKING_NOTE_PREFIX);
    }

    public static function refreshForTable(?int $tableId): void
    {
        if (!$tableId) {
            return;
        }

        $table = BanAn::query()->find($tableId);
        if (!$table || $table->trang_thai === TableStatus::NGUNG_SU_DUNG->value) {
            return;
        }

        // trang_thai_thanh_toan is now on chi_tiet_don_hang
        $baseQuery = DonHang::query()
            ->where('ban_an_id', $tableId)
            ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
            ->whereNotNull('nhan_vien_id');

        // Bàn có đơn hàng nào chưa thanh toán VÀ có chứa ít nhất 1 món ăn không?
        $hasUnpaidItems = (clone $baseQuery)
            ->whereHas('chiTietDonHang')
            ->exists();

        if ($hasUnpaidItems) {
            if ($table->trang_thai !== TableStatus::DANG_PHUC_VU->value && $table->trang_thai !== TableStatus::DA_DAT->value) {
                // Booking orders use loai_don = 'đặt hàng trước'
                $hasBookingUnpaid = (clone $baseQuery)
                    ->whereHas('chiTietDonHang', fn($q) => $q->where('loai_don', 'đặt hàng trước'))
                    ->exists();

                if ($hasBookingUnpaid && !$table->trang_thai === TableStatus::DA_DAT->value) {
                    $table->update(['trang_thai' => TableStatus::DA_DAT->value]);
                } else if (!$hasBookingUnpaid) {
                    $table->update(['trang_thai' => TableStatus::DANG_PHUC_VU->value]);
                }
            }
            return;
        }

        // Khách tự gọi tại bàn (QR/tài khoản) ĐÃ thanh toán hôm nay nhưng chưa được trả bàn:
        // khách vẫn đang ngồi → KHÔNG tự đưa bàn về trống. Nếu trả bàn lúc này, các món đã
        // thanh toán sẽ bị "biến mất" khỏi chi tiết bàn (do scope activeForTable chỉ hiện món
        // đã thanh toán khi bàn đang phục vụ). Bàn chỉ được giải phóng khi nhân viên bấm "Trả bàn".
        $hasPaidCustomerToday = DonHang::query()
            ->where('ban_an_id', $tableId)
            ->whereNull('nhan_vien_id')
            ->whereDate('created_at', today())
            ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'đã thanh toán'))
            ->exists();

        if ($hasPaidCustomerToday) {
            if ($table->trang_thai !== TableStatus::DANG_PHUC_VU->value && $table->trang_thai !== TableStatus::DA_DAT->value) {
                $table->update(['trang_thai' => TableStatus::DANG_PHUC_VU->value]);
            }
            return;
        }

        // Bàn không có đơn hàng nào có món -> trống
        if ($table->trang_thai !== TableStatus::TRONG->value) {
            $table->update(['trang_thai' => TableStatus::TRONG->value]);
        }
    }
}
