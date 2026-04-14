<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherNguoiDung extends Model
{
    use HasFactory;

    protected $table = 'voucher_nguoi_dung';

    public $timestamps = false;

    protected $fillable = [
        'nguoi_dung_id',
        'voucher_id',
        'trang_thai',
        'duoc_cap_luc',
        'da_dung_luc',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function donHang()
    {
        return $this->hasMany(DonHang::class, 'voucher_nguoi_dung_id');
    }
}