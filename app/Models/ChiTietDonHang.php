<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietDonHang extends Model
{

    protected $table = 'chi_tiet_don_hang';

    public $timestamps = false;

    protected $fillable = [
        'don_hang_id',
        'san_pham_id',
        'kich_co_id',
        'ten_san_pham',
        'ten_kich_co',
        'don_gia',
        'so_luong',
        'ghi_chu_mon',
        'thanh_tien',
        'loai_don',
        'trang_thai_thanh_toan',
        'phuong_thuc_thanh_toan',
        'thoi_gian_den',
        'so_tien_giam',
        'tong_tien',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'so_tien_giam' => 'float',
            'tong_tien' => 'float',
            'thoi_gian_den' => 'datetime',
        ];
    }

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
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