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
        'ho_ten',
        'ngay_sinh',
        'dia_chi_tam_chu',
        'so_dien_thoai',
        'ngay_vao_lam',
        'anh_dai_dien',
    ];

    protected function casts(): array
    {
        return [
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
