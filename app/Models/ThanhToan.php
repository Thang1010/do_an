<?php

namespace App\Models;

use App\Notifications\QrPaymentSuccessNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ThanhToan extends Model
{

    protected $table = 'thanh_toan';

    protected $fillable = [
        'don_hang_id',
        'ma_thanh_toan',
        'phuong_thuc',
        'so_tien',
        'trang_thai',
        'ten_ngan_hang',
        'ma_ngan_hang',
        'so_tai_khoan',
        'ten_chu_tai_khoan',
        'noi_dung_chuyen_khoan',
        'duong_dan_qr',
        'ma_giao_dich',
        'thanh_toan_luc',
        'ghi_chu',
    ];

    protected static function booted(): void
    {
        static::saved(function (ThanhToan $payment): void {
            $isPaid = in_array($payment->trang_thai, ['đã thanh toán', 'da_thanh_toan'], true);

            if (! $isPaid) {
                return;
            }

            $order = $payment->donHang;
            if (! $order) {
                return;
            }

            $order->update([
                'trang_thai_thanh_toan' => 'đã thanh toán',
                'phuong_thuc_thanh_toan' => $payment->phuong_thuc ?: $order->phuong_thuc_thanh_toan,
            ]);

            $justPaid = $payment->wasRecentlyCreated
                ? $isPaid
                : ($payment->wasChanged('trang_thai') && $isPaid);

            $isQrPayment = $payment->phuong_thuc === 'chuyển khoản' || ! empty($payment->duong_dan_qr);

            if (! $justPaid || ! $isQrPayment || ! Schema::hasTable('notifications')) {
                return;
            }

            try {
                $recipientIds = collect();

                if ($order->nhan_vien_id) {
                    $recipientIds->push((int) $order->nhan_vien_id);
                }

                $managerIds = NguoiDung::query()
                    ->whereIn('vai_tro', ['quản lý', 'chủ cửa hàng'])
                    ->where('trang_thai', 'hoạt động')
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id);

                $recipientIds = $recipientIds->merge($managerIds)->unique();

                if ($recipientIds->isNotEmpty()) {
                    NguoiDung::query()
                        ->whereIn('id', $recipientIds->all())
                        ->where('trang_thai', 'hoạt động')
                        ->get()
                        ->each
                        ->notify(new QrPaymentSuccessNotification($order, $payment));
                }
            } catch (\Throwable $e) {
                Log::warning('Khong the gui thong bao thanh toan QR thanh cong.', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }
}