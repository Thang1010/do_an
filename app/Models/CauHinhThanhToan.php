<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CauHinhThanhToan extends Model
{
    use HasFactory;

    protected $table = 'cau_hinh_thanh_toan';

    protected $fillable = [
        'ten_cau_hinh',
        'ten_ngan_hang',
        'ma_ngan_hang',
        'so_tai_khoan',
        'ten_chu_tai_khoan',
        'mo_ta',
        'mac_dinh',
        'trang_thai',
    ];

    public function thanhToan()
    {
        return $this->hasMany(ThanhToan::class, 'cau_hinh_thanh_toan_id');
    }
}