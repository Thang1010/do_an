<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DanhMuc extends Model
{
    use HasFactory;

    protected $table = 'danh_muc';

    protected $fillable = [
        'ten_danh_muc',
        'slug',
        'mo_ta',
        'trang_thai',
    ];

    public function sanPham()
    {
        return $this->hasMany(SanPham::class, 'danh_muc_id');
    }
}