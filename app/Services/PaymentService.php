<?php

namespace App\Services;

use App\Models\BanAn;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\ThanhToan;
use App\Enums\TableStatus;
use App\Traits\NormalizesPayment;
use App\Traits\ResolvesVietQrBank;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Service xử lý thanh toán chung cho Manager và Staff.
 *
 * Tập trung logic: sync ThanhToan record, tạo QR VietQR, giải phóng bàn sau thanh toán.
 */
class PaymentService
{
    use NormalizesPayment, ResolvesVietQrBank;

    /**
     * Đồng bộ bản ghi ThanhToan cho đơn hàng.
     */
    public function syncThanhToanRecord(DonHang $order, ?string $paymentMethod, string $paymentStatus): void
    {
        $record = ThanhToan::query()
            ->where('don_hang_id', $order->id)
            ->latest('id')
            ->first();

        $method = $paymentMethod ?: ($record->phuong_thuc ?? $order->phuong_thuc_thanh_toan ?? 'chuyển khoản');
        if (!in_array($method, ['tiền mặt', 'chuyển khoản'], true)) {
            $method = 'chuyển khoản';
        }

        $payload = [
            'phuong_thuc' => $method,
            'so_tien' => (float) ($order->tong_tien ?? 0),
            'trang_thai' => $this->toPaymentRecordStatus($paymentStatus),
            'thanh_toan_luc' => $paymentStatus === 'đã thanh toán' ? now() : null,
            'noi_dung_chuyen_khoan' => $method === 'chuyển khoản'
                ? ('TT ' . ($order->ma_don_hang ?? ('DON' . $order->id)))
                : null,
        ];

        if ($record) {
            $record->update($payload);
        } else {
            ThanhToan::create(array_merge([
                'don_hang_id' => $order->id,
            ], $payload));
        }

        $this->notifyOwnerIfNewlyPaid($order, $record, $paymentStatus);
    }

    /**
     * Đồng bộ bản ghi ThanhToan đơn giản (dùng bởi Staff).
     */
    public function syncThanhToanSimple(DonHang $order, string $paymentMethod, string $paymentStatus): void
    {
        $record = ThanhToan::where('don_hang_id', $order->id)->latest('id')->first();
        $payload = [
            'phuong_thuc' => $paymentMethod,
            'so_tien' => (float) ($order->tong_tien ?? 0),
            'trang_thai' => $paymentStatus === 'đã thanh toán' ? 'đã thanh toán' : 'chờ thanh toán',
            'thanh_toan_luc' => $paymentStatus === 'đã thanh toán' ? now() : null,
        ];

        if ($record) {
            $record->update($payload);
        } else {
            ThanhToan::create(array_merge(['don_hang_id' => $order->id], $payload));
        }

        $this->notifyOwnerIfNewlyPaid($order, $record, $paymentStatus);
    }

    /**
     * Gửi email cho chủ cửa hàng nếu đơn hàng vừa chuyển sang đã thanh toán.
     */
    private function notifyOwnerIfNewlyPaid(DonHang $order, ?ThanhToan $record, string $paymentStatus): void
    {
        $targetStatus = $this->toPaymentRecordStatus($paymentStatus);
        
        if ($targetStatus !== 'đã thanh toán') {
            return;
        }

        // Nếu bản ghi trước đó đã là đã thanh toán thì bỏ qua để không gửi lặp
        if ($record && $record->trang_thai === 'đã thanh toán') {
            return;
        }

        $owners = \App\Models\NguoiDung::whereIn('vai_tro', ['chủ cửa hàng'])->get();
        foreach ($owners as $owner) {
            if ($owner->email) {
                \Illuminate\Support\Facades\Mail::to($owner->email)->send(new \App\Mail\OrderPaidMail($order));
            }
        }

        // Đơn mang về (không gắn bàn) vừa thanh toán → báo nhân viên trực để pha chế & đóng gói.
        if (!$order->ban_an_id && $order->loai_don === 'mang về') {
            $this->notifyStaffNewTakeaway($order);
        }
    }

    /**
     * Thông báo cho nhân viên / quản lý / chủ cửa hàng đang hoạt động khi có đơn mang về mới.
     */
    private function notifyStaffNewTakeaway(DonHang $order): void
    {
        try {
            \App\Models\NguoiDung::query()
                ->whereIn('vai_tro', ['nhân viên', 'quản lý', 'chủ cửa hàng'])
                ->where('trang_thai', \App\Enums\UserStatus::HOAT_DONG->value)
                ->get()
                ->each(fn($user) => $user->notify(new \App\Notifications\TakeawayOrderPlacedNotification($order)));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Không thể gửi thông báo đơn mang về.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cập nhật trạng thái bàn ngay sau khi thanh toán thành công.
     */
    public function applyTableStatusAfterPayment(DonHang $order): void
    {
        if (! $order->ban_an_id) {
            return;
        }

        $table = BanAn::query()->find($order->ban_an_id);
        if (! $table || $table->trang_thai === TableStatus::NGUNG_SU_DUNG->value) {
            return;
        }

        $isBooking = \App\Services\TableStatusService::isBookingOrder($order);

        if ($isBooking && $table->trang_thai !== TableStatus::DANG_PHUC_VU->value) {
            $table->update(['trang_thai' => TableStatus::DA_DAT->value]);
            return;
        }

        if ($table->trang_thai !== TableStatus::DANG_PHUC_VU->value) {
            $table->update(['trang_thai' => TableStatus::DANG_PHUC_VU->value]);
        }
    }

    /**
     * Giải phóng bàn nếu tất cả đơn đã thanh toán.
     */
    public function freeTableIfAllPaid(?int $tableId): void
    {
        if (!$tableId) {
            return;
        }

        $hasUnpaid = DonHang::query()
            ->where('ban_an_id', $tableId)
            ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
            ->exists();

        if (!$hasUnpaid) {
            BanAn::query()->whereKey($tableId)->update([
                'trang_thai' => 'trống',
            ]);
        }
    }

    /**
     * Resolve thông tin cửa hàng cho thanh toán QR.
     */
    public function resolveStoreForPayment(?int $storeId): ?CuaHang
    {
        $store = CuaHang::query()
            ->with('chuCuaHang')
            ->when($storeId, fn(Builder $q) => $q->where('id', $storeId))
            ->first();

        if (!$store) {
            $store = CuaHang::query()
                ->with('chuCuaHang')
                ->first();
        }

        return $store;
    }
}
