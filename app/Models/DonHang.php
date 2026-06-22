<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Notifications\QrOrderPendingNotification;
use App\Notifications\CustomerOrderPlacedNotification;
use App\Services\TableStatusService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DonHang extends Model
{

    protected $table = 'don_hang';

    protected $fillable = [
        'ma_don_hang',
        'nguoi_dung_id',
        'nhan_vien_id',
        'ban_an_id',
        'voucher_nguoi_dung_id',
        'email_khach_hang',
        'da_giao_luc',
    ];

    protected $casts = [
        'da_giao_luc' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (DonHang $order): void {
            if (!Schema::hasTable('thong_bao')) {
                return;
            }

            $shouldNotify = !is_null($order->nhan_vien_id);

            if ($shouldNotify) {
                try {
                    NguoiDung::query()
                        ->whereIn('vai_tro', ['nhân viên', 'quản lý', 'chủ cửa hàng'])
                        ->where('trang_thai', UserStatus::HOAT_DONG->value)
                        ->get()
                        ->each(function ($user) use ($order) {
                            $user->notify(new QrOrderPendingNotification($order));
                        });
                } catch (\Throwable $e) {
                    Log::warning('Khong the gui thong bao don moi.', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($order->ban_an_id) {
                TableStatusService::refreshForTable($order->ban_an_id);
            }
        });

        static::deleted(function (DonHang $order): void {
            if ($order->voucher_nguoi_dung_id) {
                $vu = VoucherNguoiDung::find($order->voucher_nguoi_dung_id);
                if ($vu && $vu->trang_thai === 'đã dùng') {
                    $vu->update(['trang_thai' => 'chưa dùng', 'da_dung_luc' => null]);
                }
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

    public function danhGiaSanPham()
    {
        return $this->hasMany(DanhGiaSanPham::class, 'don_hang_id');
    }

    // ─── Virtual accessors for columns moved to chi_tiet_don_hang ───

    public function getTrangThaiThanhToanAttribute(): string
    {
        if ($this->relationLoaded('chiTietDonHang')) {
            return $this->chiTietDonHang->first()?->trang_thai_thanh_toan ?? 'chưa thanh toán';
        }
        return $this->chiTietDonHang()->value('trang_thai_thanh_toan') ?? 'chưa thanh toán';
    }

    public function getPhuongThucThanhToanAttribute(): ?string
    {
        if ($this->relationLoaded('chiTietDonHang')) {
            return $this->chiTietDonHang->first()?->phuong_thuc_thanh_toan;
        }
        return $this->chiTietDonHang()->value('phuong_thuc_thanh_toan');
    }

    public function getLoaiDonAttribute(): string
    {
        if ($this->relationLoaded('chiTietDonHang')) {
            return $this->chiTietDonHang->first()?->loai_don ?? 'đặt hàng trước';
        }
        return $this->chiTietDonHang()->value('loai_don') ?? 'đặt hàng trước';
    }

    public function getTamTinhAttribute(): float
    {
        if ($this->relationLoaded('chiTietDonHang')) {
            return (float) $this->chiTietDonHang->sum('thanh_tien');
        }
        return (float) $this->chiTietDonHang()->sum('thanh_tien');
    }

    public function getSoTienGiamAttribute(): float
    {
        if ($this->relationLoaded('chiTietDonHang')) {
            return (float) $this->chiTietDonHang->sum('so_tien_giam');
        }
        return (float) $this->chiTietDonHang()->sum('so_tien_giam');
    }

    public function getTongTienAttribute(): float
    {
        if ($this->relationLoaded('chiTietDonHang')) {
            return (float) $this->chiTietDonHang->sum('tong_tien');
        }
        return (float) $this->chiTietDonHang()->sum('tong_tien');
    }
    public function getThoiGianDenAttribute(): ?\Illuminate\Support\Carbon
    {
        if ($this->relationLoaded('chiTietDonHang')) {
            $val = $this->chiTietDonHang->first()?->thoi_gian_den;
        } else {
            $val = $this->chiTietDonHang()->value('thoi_gian_den');
        }
        return $val ? \Illuminate\Support\Carbon::parse($val) : null;
    }
    public function getGhiChuAttribute(): ?string
    {
        return null;
    }

    // ─── Helpers for updating payment status across all order items ───

    public function updatePaymentStatus(string $status, ?string $method = null): void
    {
        $data = ['trang_thai_thanh_toan' => $status];
        if ($method !== null) {
            $data['phuong_thuc_thanh_toan'] = $method;
        }
        $this->chiTietDonHang()->update($data);
    }

    // ─── Scopes to replace ->where('trang_thai_thanh_toan', ...) on DonHang ───

    public function scopeWherePayStatus($query, string $status)
    {
        return $query->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', $status));
    }

    public function scopeWhereLoaiDon($query, string $loai)
    {
        return $query->whereHas('chiTietDonHang', fn($q) => $q->where('loai_don', $loai));
    }

    /**
     * Hàng đợi đơn mang về cho nhân viên: đơn mang về, không gắn bàn, đã thanh toán
     * và chưa được đánh dấu đã giao.
     */
    public function scopeTakeawayQueue($query)
    {
        return $query->whereNull('ban_an_id')
            ->whereNull('da_giao_luc')
            ->whereHas('chiTietDonHang', fn($q) => $q->where('loai_don', 'mang về')
                ->where('trang_thai_thanh_toan', 'đã thanh toán'));
    }
}