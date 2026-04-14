<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KichCo extends Model
{
    use HasFactory;

    protected $table = 'kich_co';

    protected $fillable = [
        'ten_kich_co',
        'ma_kich_co',
        'mo_ta',
    ];

    public function sanPhamKichCo()
    {
        return $this->hasMany(SanPhamKichCo::class, 'kich_co_id');
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'kich_co_id');
    }
}