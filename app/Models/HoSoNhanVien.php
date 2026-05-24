<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoSoNhanVien extends Model
{

    protected $table = 'ho_so_nhan_vien';

    protected $fillable = [
        'nguoi_dung_id',
        'ma_nhan_vien',
        'chuc_vu_id',
        'loai_hinh_lam_viec',
        'luong_co_ban',
        'luong_theo_gio',
        'ngay_vao_lam',
    ];

    protected function casts(): array
    {
        return [
            'luong_co_ban' => 'decimal:2',
            'luong_theo_gio' => 'decimal:2',
            'ngay_vao_lam' => 'date',
        ];
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function chucVu()
    {
        return $this->belongsTo(ChucVu::class, 'chuc_vu_id');
    }
}