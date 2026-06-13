<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{

    protected $table = 'voucher';

    protected $fillable = [
        'ma_voucher',
        'ten_voucher',
        'loai_giam',
        'gia_tri_giam',
        'don_toi_thieu',
        'giam_toi_da',
        'so_luong',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'gia_tri_giam' => 'float',
            'don_toi_thieu' => 'float',
            'giam_toi_da' => 'float',
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
        ];
    }

    public function voucherNguoiDung()
    {
        return $this->hasMany(VoucherNguoiDung::class, 'voucher_id');
    }
}