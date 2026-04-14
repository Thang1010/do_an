<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'voucher';

    protected $fillable = [
        'ma_voucher',
        'ten_voucher',
        'loai_giam',
        'gia_tri_giam',
        'don_toi_thieu',
        'giam_toi_da',
        'so_luong',
        'da_su_dung',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'gia_tri_giam' => 'decimal:2',
            'don_toi_thieu' => 'decimal:2',
            'giam_toi_da' => 'decimal:2',
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
        ];
    }

    public function voucherNguoiDung()
    {
        return $this->hasMany(VoucherNguoiDung::class, 'voucher_id');
    }
}