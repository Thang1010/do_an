<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BangLuong extends Model
{

    protected $table = 'bang_luong';

    protected $fillable = [
        'nguoi_dung_id',
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
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }
}