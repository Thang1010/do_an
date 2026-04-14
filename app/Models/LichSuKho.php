<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LichSuKho extends Model
{
    use HasFactory;

    protected $table = 'lich_su_kho';

    public $timestamps = false;

    protected $fillable = [
        'nguyen_lieu_id',
        'loai_giao_dich',
        'so_luong',
        'don_gia',
        'ghi_chu',
        'nguoi_tao_id',
        'created_at',
    ];

    public function nguyenLieu()
    {
        return $this->belongsTo(NguyenLieu::class, 'nguyen_lieu_id');
    }

    public function nguoiTao()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id');
    }
}