<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichSuKho extends Model
{

    protected $table = 'lich_su_kho';

    public $timestamps = false;

    protected $fillable = [
        'nguyen_lieu_id',
        'loai_giao_dich',
        'tham_chieu_loai',
        'tham_chieu_id',
        'so_luong',
        'gia_nhap',
        'ghi_chu',
        'nguoi_tao_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tham_chieu_id' => 'integer',
            'so_luong' => 'decimal:2',
            'gia_nhap' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function nguyenLieu()
    {
        return $this->belongsTo(NguyenLieu::class, 'nguyen_lieu_id');
    }

    public function nguoiTao()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id');
    }
}