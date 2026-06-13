<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoSoKhachHang extends Model
{

    protected $table = 'ho_so_khach_hang';

    protected $fillable = [
        'nguoi_dung_id',
        'ho_ten',
        'gioi_tinh',
        'ngay_sinh',
        'dia_chi',
        'anh_dai_dien',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }
}