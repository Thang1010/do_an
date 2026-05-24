<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SanPham extends Model
{

    protected $table = 'san_pham';

    protected $fillable = [
        'danh_muc_id',
        'ten_san_pham',
        'slug',
        'mo_ta',
        'gia_goc',
        'gia_khuyen_mai',
        'hinh_anh_chinh',
        'trang_thai_ban',
        'loai_quan_ly_kho',
        'noi_bat',
    ];

    public function danhMuc()
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    public function hinhAnhSanPham()
    {
        return $this->hasMany(HinhAnhSanPham::class, 'san_pham_id');
    }

    public function chiTietDonHang()
    {
        return $this->hasMany(ChiTietDonHang::class, 'san_pham_id');
    }

    public function danhGiaSanPham()
    {
        return $this->hasMany(DanhGiaSanPham::class, 'san_pham_id');
    }

    public function sanPhamKichCo()
    {
        return $this->hasMany(SanPhamKichCo::class, 'san_pham_id');
    }

    public function congThucSanPham()
    {
        return $this->hasMany(CongThucSanPham::class, 'san_pham_id');
    }

    public function banAnDaGoi()
    {
        return $this->belongsToMany(BanAn::class, 'chi_tiet_don_hang', 'san_pham_id', 'ban_an_id')
            ->withPivot([
                'don_hang_id',
                'kich_co_id',
                'ten_san_pham',
                'ten_kich_co',
                'don_gia',
                'so_luong',
                'ghi_chu_mon',
            ]);
    }

    public function getGiaAttribute()
    {
        return $this->gia_khuyen_mai ?? $this->gia_goc;
    }

    public function getTrangThaiAttribute()
    {
        return $this->trang_thai_ban === 'đang bán' ? 'dang_ban' : 'ngung_ban';
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->hinh_anh_chinh) {
            if (Str::startsWith($this->hinh_anh_chinh, ['http://', 'https://'])) {
                return $this->hinh_anh_chinh;
            }

            return asset('storage/' . $this->hinh_anh_chinh);
        }

        return asset('images/ca_phe_nau_da.jpg');
    }

    public function nguoiDungYeuThich()
    {
        return $this->belongsToMany(NguoiDung::class, 'san_pham_yeu_thich', 'san_pham_id', 'nguoi_dung_id')
                    ->withTimestamps();
    }
}
