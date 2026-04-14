<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CongThucSanPham extends Model
{
    use HasFactory;

    protected $table = 'cong_thuc_san_pham';

    public $timestamps = false;

    protected $fillable = [
        'san_pham_id',
        'nguyen_lieu_id',
        'so_luong_can',
        'created_at',
    ];

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function nguyenLieu()
    {
        return $this->belongsTo(NguyenLieu::class, 'nguyen_lieu_id');
    }
}