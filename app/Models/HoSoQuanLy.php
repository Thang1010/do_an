<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoSoQuanLy extends Model
{
    use HasFactory;

    protected $table = 'ho_so_quan_ly';

    protected $fillable = [
        'nguoi_dung_id',
        'ma_quan_ly',
        'ngay_vao_lam',
        'so_tai_khoan',
        'ngan_hang',
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
