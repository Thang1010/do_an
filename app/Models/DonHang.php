<?php

namespace App\Models;

use App\Notifications\QrOrderPendingNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DonHang extends Model
{
    use HasFactory;

    protected $table = 'don_hang';

    protected $fillable = [
        'ma_don_hang',
        'nguoi_dung_id',
        'nhan_vien_id',
        'ban_an_id',
        'voucher_nguoi_dung_id',
        'loai_don',
        'trang_thai_don',
        'trang_thai_thanh_toan',
        'phuong_thuc_thanh_toan',
        'tam_tinh',
        'so_tien_giam',
        'tong_tien',
        'ghi_chu',
        'ten_khach_hang',
        'so_dien_thoai_khach',
        'dia_chi_giao_hang',
    ];

    protected static function booted(): void
    {
        static::saving(function (DonHang $order): void {
            // Don online khong gan ban; don tai quan / QR bat buoc co ban.
            if ($order->loai_don === 'đặt online') {
                $order->ban_an_id = null;
                return;
            }

            if (in_array($order->loai_don, ['mua tại quán', 'gọi tại bàn bằng qr'], true) && ! $order->ban_an_id) {
                throw ValidationException::withMessages([
                    'ban_an_id' => 'Đơn tại quán hoặc gọi tại bàn bằng QR phải gắn với một bàn ăn.',
                ]);
            }
        });

        static::created(function (DonHang $order): void {
            if (
                $order->loai_don !== 'gọi tại bàn bằng qr'
                || !in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan'], true)
                || !Schema::hasTable('notifications')
            ) {
                return;
            }

            try {
                NguoiDung::query()
                    ->whereIn('vai_tro', ['quản lý', 'nhân viên'])
                    ->where('trang_thai', 'hoạt động')
                    ->get()
                    ->each
                    ->notify(new QrOrderPendingNotification($order));
            } catch (\Throwable $e) {
                Log::warning('Khong the gui thong bao don QR moi.', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function nhanVien()
    {
        return $this->belongsTo(NguoiDung::class, 'nhan_vien_id');
    }

    public function banAn()
    {
        return $this->belongsTo(BanAn::class, 'ban_an_id');
    }

    public function voucherNguoiDung()
    {
        return $this->belongsTo(VoucherNguoiDung::class, 'voucher_nguoi_dung_id');
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'don_hang_id');
    }

    public function thanhToan()
    {
        return $this->hasMany(ThanhToan::class, 'don_hang_id');
    }

    public function lichSuDiemThuong()
    {
        return $this->hasMany(LichSuDiemThuong::class, 'don_hang_id');
    }

    public function danhGiaSanPham()
    {
        return $this->hasMany(DanhGiaSanPham::class, 'don_hang_id');
    }
}