<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoSoQuanLy extends Model
{

    protected $table = 'ho_so_quan_ly';

    protected $fillable = [
        'nguoi_dung_id',
        'cua_hang_id',
        'chuc_vu_id',
        'ma_quan_ly',
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

    public function cuaHang()
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }

    public function chucVu()
    {
        return $this->belongsTo(ChucVu::class, 'chuc_vu_id');
    }
}
