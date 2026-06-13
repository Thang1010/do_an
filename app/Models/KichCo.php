<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KichCo extends Model
{

    protected $table = 'kich_co';

    protected $fillable = [
        'ten_kich_co',
        'ma_kich_co',
        'mo_ta',
        'he_so_gia',
    ];

    public function sanPham()
    {
        return $this->belongsToMany(SanPham::class, 'san_pham_kich_co', 'kich_co_id', 'san_pham_id')
                    ->withTimestamps();
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'kich_co_id');
    }
}