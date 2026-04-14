<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NguyenLieu extends Model
{
    use HasFactory;

    protected $table = 'nguyen_lieu';

    protected $fillable = [
        'ten_nguyen_lieu',
        'don_vi_tinh',
        'so_luong_ton',
        'muc_canh_bao',
    ];

    public function lichSuKho()
    {
        return $this->hasMany(LichSuKho::class, 'nguyen_lieu_id');
    }

    public function congThucSanPham()
    {
        return $this->hasMany(CongThucSanPham::class, 'nguyen_lieu_id');
    }
}