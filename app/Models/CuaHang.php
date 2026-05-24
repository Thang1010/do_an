<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuaHang extends Model
{

    protected $table = 'cua_hang';

    protected $fillable = [
        'chu_cua_hang_id',
        'ten_cua_hang',
        'email',
        'so_dien_thoai',
        'dia_chi',
        'so_tai_khoan',
        'ngan_hang',
        'gio_mo_cua',
        'gio_dong_cua',
        'mo_ta',
    ];

    protected function casts(): array
    {
        return [
            'gio_mo_cua' => 'string',
            'gio_dong_cua' => 'string',
        ];
    }

    public function chuCuaHang()
    {
        return $this->belongsTo(NguoiDung::class, 'chu_cua_hang_id');
    }

    public function hoSoQuanLy()
    {
        return $this->hasMany(HoSoQuanLy::class, 'cua_hang_id');
    }

    public function nguoiDung()
    {
        return $this->hasMany(NguoiDung::class, 'cua_hang_id');
    }

    public function nhanVien()
    {
        return $this->hasMany(NguoiDung::class, 'cua_hang_id')
            ->where('vai_tro', 'nhân viên');
    }

    public function quanLy()
    {
        return $this->hasMany(NguoiDung::class, 'cua_hang_id')
            ->whereIn('vai_tro', ['quản lý', 'chủ cửa hàng']);
    }
}
