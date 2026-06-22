<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhGiaSanPham extends Model
{

    protected $table = 'danh_gia_san_pham';

    protected $fillable = [
        'nguoi_dung_id',
        'san_pham_id',
        'don_hang_id',
        'so_sao',
        'noi_dung',
        'phan_tich_cam_xuc',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }
}