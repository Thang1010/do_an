<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietDonHang extends Model
{

    protected $table = 'chi_tiet_don_hang';

    public $timestamps = false;

    protected $fillable = [
        'don_hang_id',
        'ban_an_id',
        'san_pham_id',
        'kich_co_id',
        'ten_san_pham',
        'ten_kich_co',
        'don_gia',
        'so_luong',
        'ghi_chu_mon',
        'thanh_tien',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ChiTietDonHang $detail): void {
            if (! $detail->ban_an_id && $detail->don_hang_id) {
                $detail->ban_an_id = DonHang::query()
                    ->whereKey($detail->don_hang_id)
                    ->value('ban_an_id');
            }
        });
    }

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function banAn()
    {
        return $this->belongsTo(BanAn::class, 'ban_an_id');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function kichCo()
    {
        return $this->belongsTo(KichCo::class, 'kich_co_id');
    }
}