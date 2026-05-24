<?php

namespace App\Services;

use App\Models\BanAn;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\ThanhToan;
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
            return;
        }

        ThanhToan::create(array_merge([
            'don_hang_id' => $order->id,
        ], $payload));
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
            ->where('trang_thai_don', '!=', 'đã hủy')
            ->where('trang_thai_thanh_toan', 'chưa thanh toán')
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

        if (!$store || !$store->so_tai_khoan || !$store->ngan_hang) {
            $store = CuaHang::query()
                ->with('chuCuaHang')
                ->whereNotNull('so_tai_khoan')
                ->whereNotNull('ngan_hang')
                ->first();
        }

        return $store;
    }

    /**
     * Tạo QR thanh toán VietQR cho đơn hàng.
     *
     * @return array|null null nếu không đủ thông tin
     */
    public function generateQrData(DonHang $order, CuaHang $store): ?array
    {
        $bankCode = $this->resolveVietQrBankCode($store->ngan_hang);
        $accountNo = preg_replace('/\s+/', '', (string) $store->so_tai_khoan);
        $amount = (int) round((float) ($order->tong_tien ?? 0));

        if ($bankCode === '' || $accountNo === '' || $amount <= 0) {
            return null;
        }

        $transferContent = 'TT ' . ($order->ma_don_hang ?? ('DON' . $order->id));
        $accountName = $store->chuCuaHang?->ho_ten ?? $store->ten_cua_hang;

        $params = http_build_query([
            'amount' => $amount,
            'addInfo' => $transferContent,
            'accountName' => $accountName,
        ], '', '&', PHP_QUERY_RFC3986);

        $qrUrl = "https://img.vietqr.io/image/{$bankCode}-{$accountNo}-compact2.png?{$params}";

        return [
            'qr_url' => $qrUrl,
            'order_id' => $order->id,
            'order_code' => $order->ma_don_hang,
            'amount' => $amount,
            'bank_code' => $bankCode,
            'bank_name' => $store->ngan_hang,
            'account_no' => $accountNo,
            'account_name' => $accountName,
            'transfer_content' => $transferContent,
        ];
    }

    /**
     * Tạo QR data kèm cache token + expiry (dùng bởi Manager\OrderController).
     */
    public function generateQrDataWithCache(DonHang $order, CuaHang $store): ?array
    {
        $base = $this->generateQrData($order, $store);
        if (!$base) {
            return null;
        }

        $expiresAt = now()->addSeconds(60);
        $token = (string) Str::uuid();

        Cache::put("order_payment_qr:{$token}", [
            'order_id' => $order->id,
            'table_id' => $order->ban_an_id,
            'amount' => $base['amount'],
            'transfer_content' => $base['transfer_content'],
            'expires_at' => $expiresAt->toIso8601String(),
        ], $expiresAt);

        return array_merge($base, [
            'message' => 'Đã tạo QR thanh toán. Mã sẽ hết hiệu lực sau 60 giây.',
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in' => 60,
        ]);
    }
}
