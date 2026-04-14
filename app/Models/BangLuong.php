<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BangLuong extends Model
{
    use HasFactory;

    protected $table = 'bang_luong';

    protected $fillable = [
        'nhan_vien_id',
        'thang',
        'nam',
        'tong_so_ca',
        'tong_so_gio',
        'luong_co_ban',
        'thuong',
        'khau_tru',
        'luong_thuc_nhan',
        'trang_thai',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(NguoiDung::class, 'nhan_vien_id');
    }
}