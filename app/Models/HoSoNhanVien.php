<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HoSoNhanVien extends Model
{
    use HasFactory;

    protected $table = 'ho_so_nhan_vien';

    protected $fillable = [
        'nguoi_dung_id',
        'ma_nhan_vien',
        'chuc_vu',
        'luong_co_ban',
        'ngay_vao_lam',
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
}