<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GioHang extends Model
{
    protected $table = 'gio_hang';

    protected $fillable = [
        'nguoi_dung_id',
        'san_pham_id',
        'san_pham_kich_co_id',
        'so_luong',
        'nhiet_do',
        'ghi_chu',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function sanPhamKichCo()
    {
        return $this->belongsTo(SanPhamKichCo::class, 'san_pham_kich_co_id');
    }
}
