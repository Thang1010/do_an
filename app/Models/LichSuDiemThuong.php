<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LichSuDiemThuong extends Model
{
    use HasFactory;

    protected $table = 'lich_su_diem_thuong';

    public $timestamps = false;

    protected $fillable = [
        'nguoi_dung_id',
        'don_hang_id',
        'loai_bien_dong',
        'so_diem',
        'mo_ta',
        'created_at',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }
}