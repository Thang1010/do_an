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

        $servingOrReserved = in_array($table->trang_thai, [
            TableStatus::DANG_PHUC_VU->value,
            TableStatus::DA_DAT->value,
        ], true);

        // Bàn ĐANG phục vụ/đã đặt mà còn món ĐÃ thanh toán (BẤT KỂ ngày tạo) → khách vẫn đang
        // ngồi: GIỮ nguyên, KHÔNG tự đưa bàn về trống. Áp dụng cho mọi đơn đã thanh toán (khách
        // QR tự gọi LẪN đơn nhân viên đã thu tiền). Nếu về trống, scope activeForTable sẽ ẩn các
        // món đã thanh toán khỏi chi tiết bàn (chỉ hiện khi bàn đang phục vụ) khiến món khách đã
        // trả "biến mất". Không giới hạn today() ở nhánh này để xử lý cả khách ngồi qua ngày.
        // Bàn chỉ được giải phóng khi nhân viên bấm "Trả bàn".
        $hasPaidItems = DonHang::query()
            ->where('ban_an_id', $tableId)
            ->whereNull('da_giao_luc')
            ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'đã thanh toán'))
            ->exists();

        if ($servingOrReserved && $hasPaidItems) {
            return;
        }

        // Bàn đang TRỐNG mà có đơn đã thanh toán MỚI trong hôm nay (khách vừa quét QR gọi & trả
        // tiền) → chuyển sang "đang phục vụ". Giới hạn today() để KHÔNG "hồi sinh" bàn đã trả
        // trước đó (đơn đã thanh toán cũ vẫn gắn ban_an_id nhưng không được kéo bàn về phục vụ lại).
        $hasPaidItemsToday = DonHang::query()
            ->where('ban_an_id', $tableId)
            ->whereNull('da_giao_luc')
            ->whereDate('created_at', today())
            ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'đã thanh toán'))
            ->exists();

        if ($hasPaidItemsToday) {
            if (! $servingOrReserved) {
                $table->update(['trang_thai' => TableStatus::DANG_PHUC_VU->value]);
            }
            return;
        }

        // Bàn không có đơn hàng nào có món -> trống
        if ($table->trang_thai !== TableStatus::TRONG->value) {
            $table->update(['trang_thai' => TableStatus::TRONG->value]);
            // Đóng các đơn cũ của bàn (kết thúc phiên) để không gộp lại khi bàn dùng cho khách mới.
            DonHang::closeForTable($tableId);
        }
    }
}
