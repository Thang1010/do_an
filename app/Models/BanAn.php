<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BanAn extends Model
{

    protected $table = 'ban_an';

    protected $fillable = [
        'so_ban',
        'trang_thai',
    ];

    public function donHang()
    {
        return $this->hasMany(DonHang::class, 'ban_an_id');
    }

    public function phienChat()
    {
        return $this->hasMany(PhienChat::class, 'ban_an_id');
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'ban_an_id');
    }

    public function sanPhamDaGoi()
    {
        return $this->belongsToMany(SanPham::class, 'chi_tiet_don_hang', 'ban_an_id', 'san_pham_id')
            ->withPivot([
                'don_hang_id',
                'kich_co_id',
                'ten_san_pham',
                'ten_kich_co',
                'don_gia',
                'so_luong',
                'ghi_chu_mon',
            ]);
    }
}